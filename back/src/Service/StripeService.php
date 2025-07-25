<?php

namespace App\Service;

use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Webhook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class StripeService
{
    /** @var array<string, string> */
    private array $priceMap;

    private StripeClient $stripeClient;

    /**
     * @param array<string, string> $stripePrices
     */
    public function __construct(
        #[Autowire('%stripe_secret_key%')]
        private string $stripeSecretKey,
        #[Autowire('%stripe_prices%')]
        array $stripePrices,
        #[Autowire('%stripe_webhook_secret%')]
        private string $stripeWebhookSecret,
    ) {
        $this->stripeClient = new StripeClient($this->stripeSecretKey);
        $this->priceMap = $stripePrices;
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

        return $this->stripeClient->checkout->sessions->create($sessionData);
    }

    /**
     * Construct a webhook event from payload and signature.
     *
     * @SuppressWarnings("PHPMD.StaticAccess")
     *
     * @throws SignatureVerificationException If the signature verification fails
     */
    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        // Webhook::constructEvent is a static method but it's unavoidable in Stripe's SDK
        // This is a valid use case for static methods as it's a factory method
        return Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
    }

    /**
     * Get line items for a checkout session.
     *
     * @return StripeObject Line items object containing data array
     */
    public function getSessionLineItems(string $sessionId): StripeObject
    {
        return $this->stripeClient->checkout->sessions->allLineItems($sessionId);
    }

    public function cancelSubscription(string $subscriptionId): StripeObject
    {
        return $this->stripeClient->subscriptions->cancel($subscriptionId, [
            'prorate' => true,
        ]);
    }

    public function changeSubscriptionPlan(string $subscriptionId, string $newPriceId): StripeObject
    {
        return $this->stripeClient->subscriptions->update($subscriptionId, [
            'cancel_at_period_end' => false,
            'proration_behavior' => 'create_prorations',
            'items' => [
                [
                    'id' => $this->getSubscriptionItemId($subscriptionId),
                    'price' => $newPriceId,
                ],
            ],
        ]);
    }

    private function getSubscriptionItemId(string $subscriptionId): string
    {
        $subscription = $this->stripeClient->subscriptions->retrieve($subscriptionId, [
            'expand' => ['items'],
        ]);

        // Assume the first item is the one we want to modify (most subscriptions have only one item)
        return $subscription->items->data[0]->id;
    }
}
