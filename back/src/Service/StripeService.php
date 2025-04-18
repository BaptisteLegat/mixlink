<?php

namespace App\Service;

use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\StripeObject;
use Stripe\Webhook;

class StripeService
{
    /** @var array<string, string> */
    private array $priceMap;

    /**
     * @param array<string, string> $stripePrices
     */
    public function __construct(
        private string $stripeSecretKey,
        array $stripePrices,
        private string $stripeWebhookSecret,
    ) {
        $this->initializeStripe();
        $this->priceMap = $stripePrices;
    }

    private function initializeStripe(): void
    {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function getPriceIdForPlan(string $planName): ?string
    {
        return $this->priceMap[strtolower($planName)] ?? null;
    }

    /**
     * Create a new Stripe checkout session.
     */
    public function createCheckoutSession(string $priceId, string $successUrl, string $cancelUrl, ?string $customerEmail = null): Session
    {
        $sessionData = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ];

        if (null !== $customerEmail) {
            $sessionData['customer_email'] = $customerEmail;
        }

        return Session::create($sessionData);
    }

    /**
     * Construct a webhook event from payload and signature.
     *
     * @throws SignatureVerificationException If the signature verification fails
     */
    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
    }

    /**
     * Get line items for a checkout session.
     *
     * @return StripeObject Line items object containing data array
     */
    public function getSessionLineItems(string $sessionId): StripeObject
    {
        return Session::allLineItems($sessionId);
    }
}
