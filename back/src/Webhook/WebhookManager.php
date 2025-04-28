<?php

namespace App\Webhook;

use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
use Exception;
use Stripe\Checkout\Session;
use Stripe\StripeObject;
use Symfony\Component\HttpFoundation\Response;

class WebhookManager
{
    public function __construct(
        private PlanRepository $planRepository,
        private UserRepository $userRepository,
        private StripeService $stripeService,
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
}
