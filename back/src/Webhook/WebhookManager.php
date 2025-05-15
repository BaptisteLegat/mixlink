<?php

namespace App\Webhook;

use App\Repository\PlanRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeObject;
use Stripe\Subscription as StripeSubscription;
use Symfony\Component\HttpFoundation\Response;

class WebhookManager
{
    public const string EVENT_CHECKOUT_SESSION_COMPLETED = 'checkout.session.completed';
    public const string EVENT_SUBSCRIPTION_UPDATED = 'customer.subscription.updated';
    public const string EVENT_SUBSCRIPTION_DELETED = 'customer.subscription.deleted';

    public function __construct(
        private PlanRepository $planRepository,
        private UserRepository $userRepository,
        private StripeService $stripeService,
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionManager $subscriptionManager,
        private LoggerInterface $logger,
    ) {
    }

    public function handleCheckoutSessionCompletedEvent(Event $event): Response
    {
        $session = $event->data->object;

        if (!$session instanceof Session) {
            $this->logger->error('Invalid session object');

            return new Response('Invalid session object', Response::HTTP_BAD_REQUEST);
        }

        return $this->handleCheckoutSessionCompleted($session);
    }

    public function handleSubscriptionUpdatedEvent(Event $event): Response
    {
        $subscription = $event->data->object;

        if (!$subscription instanceof StripeSubscription) {
            $this->logger->error('Invalid subscription object');

            return new Response('Invalid subscription object', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->handleSubscriptionUpdated($subscription);

            return new Response('Subscription update handled', Response::HTTP_OK);
        } catch (Exception $e) {
            $this->logger->error('Failed to handle subscription update: '.$e->getMessage());

            return new Response('Failed to handle subscription update: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function handleSubscriptionCanceledEvent(Event $event): Response
    {
        $subscription = $event->data->object;

        if (!$subscription instanceof StripeSubscription) {
            $this->logger->error('Invalid subscription object');

            return new Response('Invalid subscription object', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->handleSubscriptionCanceled($subscription);

            return new Response('Subscription cancellation handled', Response::HTTP_OK);
        } catch (Exception $e) {
            $this->logger->error('Failed to handle subscription cancellation: '.$e->getMessage());

            return new Response('Failed to handle subscription cancellation: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function handleCheckoutSessionCompleted(Session $session): Response
    {
        /** @var string|null $email */
        $email = $session->customer_email ?? null;
        /** @var string|null $stripeSubscriptionId */
        $stripeSubscriptionId = $session->subscription ?? null;
        $sessionId = $session->id;

        if (null === $email || null === $stripeSubscriptionId) {
            return new Response('Missing required data: email or subscription ID', Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                return new Response('User not found', Response::HTTP_NOT_FOUND);
            }

            $priceId = $this->getPriceIdFromSession($sessionId);
            if (null === $priceId) {
                return new Response('Price ID not found', Response::HTTP_BAD_REQUEST);
            }

            $plan = $this->planRepository->findOneBy(['stripePriceId' => $priceId]);
            if (!$plan) {
                return new Response('Plan not found', Response::HTTP_NOT_FOUND);
            }

            $this->subscriptionManager->create($user, $plan, $stripeSubscriptionId);

            return new Response('Webhook handled', Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response('Server error: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPriceIdFromSession(string $sessionId): ?string
    {
        /** @var StripeObject $lineItems */
        $lineItems = $this->stripeService->getSessionLineItems($sessionId);

        /** @var array<int, StripeObject> $lineItemsData */
        $lineItemsData = $lineItems->data ?? [];
        if (empty($lineItemsData)) {
            return null;
        }

        /** @var StripeObject|null $lineItem */
        $lineItem = $lineItemsData[0] ?? null;
        if (!$lineItem) {
            return null;
        }

        /** @var StripeObject|null $price */
        $price = $lineItem->price ?? null;
        if (!$price || !isset($price->id)) {
            return null;
        }

        /** @var string */
        return $price->id;
    }

    public function handleSubscriptionUpdated(StripeSubscription $stripeSubscription): void
    {
        try {
            $subscription = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSubscription->id]);

            if (!$subscription) {
                $this->logger->info('Subscription not found in database for update event', [
                    'stripeSubscriptionId' => $stripeSubscription->id,
                    'context' => 'This might be normal for new subscriptions',
                ]);

                return;
            }

            $subscription->setStatus($stripeSubscription->status);

            if ('canceled' === $stripeSubscription->status) {
                $subscription->setCanceledAt(new DateTimeImmutable());
            }

            $subscription->setCanceledAt($stripeSubscription->cancel_at_period_end ? new DateTimeImmutable() : null);

            if (isset($stripeSubscription->current_period_end) && is_int($stripeSubscription->current_period_end)) {
                $endDate = new DateTimeImmutable('@'.$stripeSubscription->current_period_end);
                $subscription->setEndDate($endDate);
            }

            if (!empty($stripeSubscription->items->data[0]->price->id)) {
                $priceId = $stripeSubscription->items->data[0]->price->id;
                $plan = $this->planRepository->findOneBy(['stripePriceId' => $priceId]);

                if ($plan) {
                    $subscription->setPlan($plan);
                }
            }

            $this->subscriptionRepository->save($subscription, true);
        } catch (Exception $e) {
            $this->logger->error('Error updating subscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stripeSubscriptionId' => $stripeSubscription->id,
            ]);
            throw $e;
        }
    }

    public function handleSubscriptionCanceled(StripeSubscription $stripeSubscription): void
    {
        $subscription = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSubscription->id]);

        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        $subscription->setCanceledAt(new DateTimeImmutable());
        $subscription->setStatus('canceled');

        if ('canceled' === $stripeSubscription->status) {
            $endDate = new DateTimeImmutable();
            $subscription->setEndDate($endDate);
        } elseif ($stripeSubscription->cancel_at_period_end) {
            /** @var int $currentPeriodEnd */
            $currentPeriodEnd = $stripeSubscription->offsetGet('current_period_end');
            $endDate = new DateTimeImmutable('@'.strval($currentPeriodEnd));
            $subscription->setEndDate($endDate);
        }

        $this->subscriptionRepository->save($subscription, true);
    }
}
