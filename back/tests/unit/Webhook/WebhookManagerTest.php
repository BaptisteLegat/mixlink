<?php

namespace App\Tests\Webhook;

use App\Entity\Plan;
use App\Entity\User;
use App\Repository\PlanRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
use App\Webhook\WebhookManager;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\StripeObject;
use Symfony\Component\HttpFoundation\Response;

class WebhookManagerTest extends TestCase
{
    private PlanRepository|MockObject $planRepository;
    private UserRepository|MockObject $userRepository;
    private StripeService|MockObject $stripeService;
    private SubscriptionRepository|MockObject $subscriptionRepository;
    private SubscriptionManager|MockObject $subscriptionManager;
    private WebhookManager $webhookManager;

    protected function setUp(): void
    {
        $this->planRepository = $this->createMock(PlanRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->stripeService = $this->createMock(StripeService::class);
        $this->subscriptionRepository = $this->createMock(SubscriptionRepository::class);
        $this->subscriptionManager = $this->createMock(SubscriptionManager::class);

        $this->webhookManager = new WebhookManager(
            $this->planRepository,
            $this->userRepository,
            $this->stripeService,
            $this->subscriptionRepository,
            $this->subscriptionManager
        );
    }

    public function testHandleCheckoutSessionCompletedMissingEmail(): void
    {
        $session = $this->createSessionMock(null, 'sub_123', 'cs_123');

        $response = $this->webhookManager->handleCheckoutSessionCompleted($session);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Missing required data: email or subscription ID', $response->getContent());
    }

    public function testHandleCheckoutSessionCompletedMissingSubscription(): void
    {
        $session = $this->createSessionMock('user@example.com', null, 'cs_123');

        $response = $this->webhookManager->handleCheckoutSessionCompleted($session);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
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

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
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

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Price ID not found', $response->getContent());
    }

    public function testHandleCheckoutSessionCompletedPlanNotFound(): void
    {
        $session = $this->createSessionMock('user@example.com', 'sub_123', 'cs_123');
        $user = new User();
        $priceId = 'price_123';

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn($user)
        ;

        $this->mockGetPriceIdFromSession('cs_123', $priceId);

        $this->planRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripePriceId' => $priceId])
            ->willReturn(null)
        ;

        $response = $this->webhookManager->handleCheckoutSessionCompleted($session);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
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
            ->with(['email' => 'user@example.com'])
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

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Webhook handled', $response->getContent());
    }

    public function testHandleCheckoutSessionCompletedWithException(): void
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
            ->willThrowException(new Exception('Test exception'))
        ;

        $response = $this->webhookManager->handleCheckoutSessionCompleted($session);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('Server error: Test exception', $response->getContent());
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

    public function testGetPriceIdFromSessionNullLineItem(): void
    {
        $lineItemsData = ['data' => [null]];
        $lineItems = StripeObject::constructFrom($lineItemsData);

        $this->stripeService->expects($this->once())
            ->method('getSessionLineItems')
            ->with('cs_123')
            ->willReturn($lineItems)
        ;

        $result = $this->webhookManager->getPriceIdFromSession('cs_123');
        $this->assertNull($result);
    }

    public function testGetPriceIdFromSessionMissingPrice(): void
    {
        $lineItemData = ['some_other_field' => 'value'];
        $lineItemsData = ['data' => [$lineItemData]];
        $lineItems = StripeObject::constructFrom($lineItemsData);

        $this->stripeService->expects($this->once())
            ->method('getSessionLineItems')
            ->with('cs_123')
            ->willReturn($lineItems)
        ;

        $result = $this->webhookManager->getPriceIdFromSession('cs_123');
        $this->assertNull($result);
    }

    private function createSessionMock(?string $email, ?string $subscriptionId, string $sessionId): Session
    {
        $session = $this->createMock(Session::class);

        $sessionData = [
            'customer_email' => $email,
            'subscription' => $subscriptionId,
            'id' => $sessionId,
        ];

        $session->method('__get')
            ->willReturnCallback(function ($name) use ($sessionData) {
                return $sessionData[$name] ?? null;
            })
        ;

        $session->method('__isset')
            ->willReturnCallback(function ($name) use ($sessionData) {
                return isset($sessionData[$name]);
            })
        ;

        return $session;
    }

    private function mockGetPriceIdFromSession(string $sessionId, string $priceId): void
    {
        $priceData = ['id' => $priceId];
        $lineItemData = ['price' => $priceData];
        $lineItemsData = ['data' => [$lineItemData]];

        $lineItems = StripeObject::constructFrom($lineItemsData);

        $this->stripeService->expects($this->once())
            ->method('getSessionLineItems')
            ->with($sessionId)
            ->willReturn($lineItems)
        ;
    }
}
