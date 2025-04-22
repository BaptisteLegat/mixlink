<?php

namespace App\Tests\Webhook;

use App\Entity\Plan;
use App\Entity\User;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
use App\Webhook\WebhookManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\StripeObject;

class WebhookManagerTest extends TestCase
{
    private PlanRepository|MockObject $planRepository;
    private UserRepository|MockObject $userRepository;
    private StripeService|MockObject $stripeService;
    private SubscriptionManager|MockObject $subscriptionManager;
    private WebhookManager $webhookManager;

    protected function setUp(): void
    {
        $this->planRepository = $this->createMock(PlanRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->stripeService = $this->createMock(StripeService::class);
        $this->subscriptionManager = $this->createMock(SubscriptionManager::class);

        $this->webhookManager = new WebhookManager(
            $this->planRepository,
            $this->userRepository,
            $this->stripeService,
            $this->subscriptionManager
        );
    }

    public function testHandleCheckoutSessionCompletedMissingEmail(): void
    {
        $session = $this->createSessionMock(null, 'sub_123', 'cs_123');

        $response = $this->webhookManager->handleCheckoutSessionCompleted($session);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Missing required data: email or subscription ID', $response->getContent());
    }

    public function testHandleCheckoutSessionCompletedMissingSubscription(): void
    {
        $session = $this->createSessionMock('user@example.com', null, 'cs_123');

        $response = $this->webhookManager->handleCheckoutSessionCompleted($session);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Missing required data: email or subscription ID', $response->getContent());
    }

    public function testHandleCheckoutSessionCompletedUserNotFound(): void
    {
        $session = $this->createSessionMock('user@example.com', 'sub_123', 'cs_123');

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn(null)
        ;

        $response = $this->webhookManager->handleCheckoutSessionCompleted($session);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('User not found', $response->getContent());
    }

    public function testHandleCheckoutSessionCompletedPriceIdNotFound(): void
    {
        $session = $this->createSessionMock('user@example.com', 'sub_123', 'cs_123');
        $user = new User();

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn($user)
        ;

        $this->stripeService->expects($this->once())
            ->method('getSessionLineItems')
            ->with('cs_123')
            ->willReturn(new StripeObject())
        ;

        $response = $this->webhookManager->handleCheckoutSessionCompleted($session);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Price ID not found', $response->getContent());
    }

    public function testHandleCheckoutSessionCompletedPlanNotFound(): void
    {
        $session = $this->createSessionMock('user@example.com', 'sub_123', 'cs_123');
        $user = new User();
        $priceId = 'price_123';

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user)
        ;

        $this->mockGetPriceIdFromSession('cs_123', $priceId);

        $this->planRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripePriceId' => $priceId])
            ->willReturn(null)
        ;

        $response = $this->webhookManager->handleCheckoutSessionCompleted($session);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Plan not found', $response->getContent());
    }

    public function testHandleCheckoutSessionCompletedSuccess(): void
    {
        $session = $this->createSessionMock('user@example.com', 'sub_123', 'cs_123');
        $user = new User();
        $priceId = 'price_123';
        $plan = new Plan();

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user)
        ;

        $this->mockGetPriceIdFromSession('cs_123', $priceId);

        $this->planRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripePriceId' => $priceId])
            ->willReturn($plan)
        ;

        $this->subscriptionManager->expects($this->once())
            ->method('create')
            ->with($user, $plan, 'sub_123')
        ;

        $response = $this->webhookManager->handleCheckoutSessionCompleted($session);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Webhook handled', $response->getContent());
    }

    public function testGetPriceIdFromSessionEmptyLineItems(): void
    {
        $lineItems = new StripeObject();
        $this->stripeService->expects($this->once())
            ->method('getSessionLineItems')
            ->with('cs_123')
            ->willReturn($lineItems)
        ;

        $result = $this->webhookManager->getPriceIdFromSession('cs_123');
        $this->assertNull($result);
    }

    public function testGetPriceIdFromSessionSuccess(): void
    {
        $price = new StripeObject(['id' => 'price_123']);
        $lineItem = new StripeObject(['price' => $price]);
        $lineItems = new StripeObject(['data' => [$lineItem]]);

        $this->stripeService->expects($this->once())
            ->method('getSessionLineItems')
            ->with('cs_123')
            ->willReturn($lineItems)
        ;

        $result = $this->webhookManager->getPriceIdFromSession('cs_123');
        $this->assertEquals('price_123', $result);
    }

    private function createSessionMock(?string $email, ?string $subscriptionId, string $sessionId): Session
    {
        $session = $this->createMock(Session::class);

        $session->customer_email = $email;
        $session->subscription = $subscriptionId;
        $session->id = $sessionId;

        return $session;
    }

    private function mockGetPriceIdFromSession(string $sessionId, string $priceId): void
    {
        $price = new StripeObject(['id' => $priceId]);
        $lineItem = new StripeObject(['price' => $price]);
        $lineItems = new StripeObject(['data' => [$lineItem]]);

        $this->stripeService->expects($this->once())
            ->method('getSessionLineItems')
            ->with($sessionId)
            ->willReturn($lineItems)
        ;
    }
}
