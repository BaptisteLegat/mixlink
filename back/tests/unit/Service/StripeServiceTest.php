<?php

namespace App\Tests\Unit\Service;

use App\Service\StripeService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Service\Checkout\SessionService;
use Stripe\StripeClient;
use Stripe\StripeObject;

/**
 * Stub of StripeClient to mock the checkout sessions.
 */
class StripeClientStub extends StripeClient
{
    public object $checkout;

    public function __construct(object $checkoutMock)
    {
        parent::__construct('sk_test_dummy');
        $this->checkout = $checkoutMock;
    }
}

class StripeServiceTest extends TestCase
{
    private const STRIPE_SECRET_KEY = 'test_stripe_secret_key';
    private const STRIPE_WEBHOOK_SECRET = 'test_webhook_secret';

    /**
     * @var array<string, string>
     */
    private array $priceMaps = [
        'basic' => 'price_basic123',
        'premium' => 'price_premium456',
        'pro' => 'price_pro789',
    ];

    private StripeService $stripeService;
    private ReflectionProperty $stripeClientProperty;

    protected function setUp(): void
    {
        $this->stripeService = new StripeService(
            self::STRIPE_SECRET_KEY,
            $this->priceMaps,
            self::STRIPE_WEBHOOK_SECRET
        );

        $this->stripeClientProperty = new ReflectionProperty(StripeService::class, 'stripeClient');
        $this->stripeClientProperty->setAccessible(true);
    }

    public function testGetPriceIdForPlan(): void
    {
        $this->assertEquals('price_basic123', $this->stripeService->getPriceIdForPlan('basic'));
        $this->assertEquals('price_premium456', $this->stripeService->getPriceIdForPlan('premium'));
        $this->assertEquals('price_pro789', $this->stripeService->getPriceIdForPlan('pro'));
        $this->assertEquals('price_basic123', $this->stripeService->getPriceIdForPlan('BASIC'));
        $this->assertNull($this->stripeService->getPriceIdForPlan('nonexistent'));
    }

    public function testCreateCheckoutSession(): void
    {
        $priceId = 'price_test123';
        $successUrl = 'https://example.com/success';
        $cancelUrl = 'https://example.com/cancel';
        $customerEmail = 'test@example.com';

        $sessionMock = $this->createMock(Session::class);
        $sessionsMock = $this->createMock(SessionService::class);

        $sessionsMock->expects($this->once())
            ->method('create')
            ->with([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => $customerEmail,
            ])
            ->willReturn($sessionMock);

        $this->stripeClientProperty->setValue(
            $this->stripeService,
            new StripeClientStub((object) ['sessions' => $sessionsMock])
        );

        $result = $this->stripeService->createCheckoutSession($priceId, $successUrl, $cancelUrl, $customerEmail);
        $this->assertSame($sessionMock, $result);
    }

    public function testCreateCheckoutSessionWithoutCustomerEmail(): void
    {
        $priceId = 'price_test123';
        $successUrl = 'https://example.com/success';
        $cancelUrl = 'https://example.com/cancel';

        $sessionMock = $this->createMock(Session::class);
        $sessionsMock = $this->createMock(SessionService::class);

        $sessionsMock->expects($this->once())
            ->method('create')
            ->with([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ])
            ->willReturn($sessionMock);

        $this->stripeClientProperty->setValue(
            $this->stripeService,
            new StripeClientStub((object) ['sessions' => $sessionsMock])
        );

        $result = $this->stripeService->createCheckoutSession($priceId, $successUrl, $cancelUrl);
        $this->assertSame($sessionMock, $result);
    }

    public function testConstructWebhookEventThrowsException(): void
    {
        $payload = 'invalid_payload';
        $signature = 'invalid_signature';

        $this->expectException(SignatureVerificationException::class);

        $this->stripeService->constructWebhookEvent($payload, $signature);
    }

    public function testGetSessionLineItems(): void
    {
        $sessionId = 'cs_test_123';
        $lineItemsMock = $this->createMock(StripeObject::class);

        $sessionsMock = $this->createMock(SessionService::class);
        $sessionsMock->expects($this->once())
            ->method('allLineItems')
            ->with($sessionId)
            ->willReturn($lineItemsMock);

        $this->stripeClientProperty->setValue(
            $this->stripeService,
            new StripeClientStub((object) ['sessions' => $sessionsMock])
        );

        $result = $this->stripeService->getSessionLineItems($sessionId);
        $this->assertSame($lineItemsMock, $result);
    }

    public function testCancelSubscription(): void
    {
        $subscriptionId = 'sub_test_123';
        $expectedResult = $this->createMock(StripeObject::class);

        $subscriptionsMock = new class($expectedResult) {
            private $returnValue;
            public $called = false;
            public $args;

            public function __construct($returnValue)
            {
                $this->returnValue = $returnValue;
            }

            public function cancel($subscriptionId, $options)
            {
                $this->called = true;
                $this->args = [$subscriptionId, $options];

                return $this->returnValue;
            }
        };

        $stripeClientStub = new class($subscriptionsMock) extends StripeClient {
            public object $subscriptions;

            public function __construct($subscriptions)
            {
                parent::__construct('sk_test_dummy');
                $this->subscriptions = $subscriptions;
            }
        };

        $this->stripeClientProperty->setValue($this->stripeService, $stripeClientStub);

        $result = $this->stripeService->cancelSubscription($subscriptionId);
        $this->assertSame($expectedResult, $result);
    }

    public function testChangeSubscriptionPlan(): void
    {
        $subscriptionId = 'sub_test_456';
        $newPriceId = 'price_new_789';
        $expectedResult = $this->createMock(StripeObject::class);

        $subscriptionItemId = 'si_123abc';
        $subscriptionRetrieveMock = (object) [
            'items' => (object) [
                'data' => [
                    (object) ['id' => $subscriptionItemId],
                ],
            ],
        ];

        $subscriptionsMock = new class($subscriptionRetrieveMock, $expectedResult) {
            private $retrieveReturn;
            private $updateReturn;
            public $retrieveCalled = false;
            public $updateCalled = false;
            public $retrieveArgs;
            public $updateArgs;

            public function __construct($retrieveReturn, $updateReturn)
            {
                $this->retrieveReturn = $retrieveReturn;
                $this->updateReturn = $updateReturn;
            }

            public function retrieve($subscriptionId, $options)
            {
                $this->retrieveCalled = true;
                $this->retrieveArgs = [$subscriptionId, $options];

                return $this->retrieveReturn;
            }

            public function update($subscriptionId, $params)
            {
                $this->updateCalled = true;
                $this->updateArgs = [$subscriptionId, $params];

                return $this->updateReturn;
            }
        };

        $stripeClientStub = new class($subscriptionsMock) extends StripeClient {
            public object $subscriptions;

            public function __construct($subscriptions)
            {
                parent::__construct('sk_test_dummy');
                $this->subscriptions = $subscriptions;
            }
        };

        $this->stripeClientProperty->setValue($this->stripeService, $stripeClientStub);

        $result = $this->stripeService->changeSubscriptionPlan($subscriptionId, $newPriceId);
        $this->assertSame($expectedResult, $result);
    }
}
