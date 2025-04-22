<?php

namespace App\Webhook;

use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
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

        // Session id is never null when working with a valid Session object
        $sessionId = $session->id;

        if (null === $email || null === $stripeSubscriptionId) {
            return new Response('Missing required data: email or subscription ID', 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return new Response('User not found', 404);
        }

        $priceId = $this->getPriceIdFromSession($sessionId);
        if (null === $priceId) {
            return new Response('Price ID not found', 400);
        }

        $plan = $this->planRepository->findOneBy(['stripePriceId' => $priceId]);
        if (!$plan) {
            return new Response('Plan not found', 404);
        }

        $this->subscriptionManager->create($user, $plan, $stripeSubscriptionId);

        return new Response('Webhook handled', 200);
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
