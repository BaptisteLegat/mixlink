<?php

namespace App\Webhook;

use App\Repository\PlanRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
use DateTimeImmutable;
use Exception;
use Stripe\Checkout\Session;
use Stripe\StripeObject;
use Stripe\Subscription as StripeSubscription;
use Symfony\Component\HttpFoundation\Response;

class WebhookManager
{
    public function __construct(
        private PlanRepository $planRepository,
        private UserRepository $userRepository,
        private StripeService $stripeService,
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionManager $subscriptionManager,
    ) {
    }

    /**
     * Handle checkout.session.completed events.
     */
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

    /**
     * Extract price ID from session line items.
     */
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
        // Find subscription by Stripe ID
        $subscription = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSubscription->id]);

        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        // Mettre à jour le statut (pas besoin de vérifier isset car le type est défini dans StripeSubscription)
        $subscription->setStatus($stripeSubscription->status);

        // Si le statut est "canceled", mettre à jour la date d'annulation
        if ('canceled' === $stripeSubscription->status) {
            $subscription->setCanceledAt(new DateTimeImmutable());
        }

        // Vérifier l'annulation future
        if ($stripeSubscription->cancel_at_period_end) {
            $subscription->setCanceledAt(new DateTimeImmutable());
        } else {
            // L'annulation a été révoquée
            $subscription->setCanceledAt(null);
        }

        // Update end date based on current_period_end
        /** @var int $currentPeriodEnd */
        $currentPeriodEnd = $stripeSubscription->offsetGet('current_period_end');
        $endDate = new DateTimeImmutable('@'.strval($currentPeriodEnd));
        $subscription->setEndDate($endDate);

        // If subscription has a new price, update the plan
        if (isset($stripeSubscription->items->data[0]->price->id)) {
            $priceId = $stripeSubscription->items->data[0]->price->id;
            $plan = $this->planRepository->findOneBy(['stripePriceId' => $priceId]);

            if ($plan) {
                $subscription->setPlan($plan);
            }
        }

        $this->subscriptionRepository->save($subscription, true);
    }

    public function handleSubscriptionCanceled(StripeSubscription $stripeSubscription): void
    {
        $subscription = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSubscription->id]);

        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        // Marquer l'abonnement comme annulé immédiatement
        $subscription->setCanceledAt(new DateTimeImmutable());
        $subscription->setStatus('canceled');

        // Si l'annulation est immédiate, mettre à jour la date de fin
        if ('canceled' === $stripeSubscription->status) {
            $endDate = new DateTimeImmutable();
            $subscription->setEndDate($endDate);
        }
        // Sinon, garder la date de fin actuelle (fin de la période en cours)
        elseif ($stripeSubscription->cancel_at_period_end) {
            /** @var int $currentPeriodEnd */
            $currentPeriodEnd = $stripeSubscription->offsetGet('current_period_end');
            $endDate = new DateTimeImmutable('@'.strval($currentPeriodEnd));
            $subscription->setEndDate($endDate);
        }

        $this->subscriptionRepository->save($subscription, true);
    }
}
