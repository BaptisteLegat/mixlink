<?php

namespace App\Service;

use Stripe\Checkout\Session;
use Stripe\Stripe;

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
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
        $this->priceMap = $stripePrices;
    }

    public function getPriceIdForPlan(string $planName): ?string
    {
        return $this->priceMap[strtolower($planName)] ?? null;
    }

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
}
