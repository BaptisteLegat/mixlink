<?php

namespace App\Tests\Webhook;

use App\Entity\Plan;
use App\Entity\Subscription;
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
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeObject;
use Stripe\Subscription as StripeSubscription;
use Symfony\Component\HttpFoundation\Response;

class WebhookManagerTest extends TestCase
{
    private PlanRepository|MockObject $planRepository;
    private UserRepository|MockObject $userRepository;
    private StripeService|MockObject $stripeService;
    private SubscriptionRepository|MockObject $subscriptionRepository;
    private SubscriptionManager|MockObject $subscriptionManager;
    private LoggerInterface|MockObject $logger;
    private WebhookManager $webhookManager;

    protected function setUp(): void
    {
        $this->planRepository = $this->createMock(PlanRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->stripeService = $this->createMock(StripeService::class);
        $this->subscriptionRepository = $this->createMock(SubscriptionRepository::class);
        $this->subscriptionManager = $this->createMock(SubscriptionManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->webhookManager = new WebhookManager(
            $this->planRepository,
            $this->userRepository,
            $this->stripeService,
            $this->subscriptionRepository,
            $this->subscriptionManager,
            $this->logger
        );
    }

    public function testHandleCheckoutSessionCompltedEventInvalidSession(): void
    {
        $event = new Event();

        $event->data = new StripeObject();
        $event->data->object = new StripeObject();
        $event->data->object->type = 'invalid_type';

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Invalid session object')
        ;
        $response = $this->webhookManager->handleCheckoutSessionCompletedEvent($event);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid session object', $response->getContent());
    }

    public function testHandleCheckoutSessionCompletedEventValidSession(): void
    {
        $event = new Event();

        $sessionData = [
            'id' => 'cs_123',
            'customer_email' => 'test@example.com',
            'subscription' => 'sub_123',
        ];
        $session = Session::constructFrom($sessionData);

        $event->data = new StripeObject();
        $event->data->object = $session;

        $user = new User();
        $plan = new Plan();

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user)
        ;

        $this->stripeService->expects($this->once())
            ->method('getSessionLineItems')
            ->with('cs_123')
            ->willReturn(StripeObject::constructFrom([
                'data' => [
                    ['price' => ['id' => 'price_123']],
                ],
            ]))
        ;

        $this->planRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripePriceId' => 'price_123'])
            ->willReturn($plan)
        ;

        $this->subscriptionManager->expects($this->once())
            ->method('create')
            ->with($user, $plan, 'sub_123')
        ;

        $response = $this->webhookManager->handleCheckoutSessionCompletedEvent($event);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Webhook handled', $response->getContent());
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

    public function testHandleSubscriptionUpdatedEventInvalidSubscription(): void
    {
        $event = new Event();

        $event->data = new StripeObject();
        $event->data->object = new StripeObject();

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Invalid subscription object')
        ;

        $response = $this->webhookManager->handleSubscriptionUpdatedEvent($event);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid subscription object', $response->getContent());
    }

    public function testHandleSubscriptionUpdatedEventSuccess(): void
    {
        $event = new Event();

        $subscriptionData = [
            'id' => 'sub_123',
            'status' => 'active',
            'current_period_end' => time() + 86400, // +24h
            'cancel_at_period_end' => false,
            'items' => [
                'data' => [
                    [
                        'price' => [
                            'id' => 'price_123',
                        ],
                    ],
                ],
            ],
        ];
        $subscription = StripeSubscription::constructFrom($subscriptionData);

        $event->data = new StripeObject();
        $event->data->object = $subscription;

        $dbSubscription = $this->createMock(Subscription::class);
        $plan = new Plan();

        $this->subscriptionRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeSubscriptionId' => 'sub_123'])
            ->willReturn($dbSubscription)
        ;

        $dbSubscription->expects($this->once())
            ->method('setStatus')
            ->with('active')
            ->willReturnSelf()
        ;

        $dbSubscription->expects($this->once())
            ->method('setCanceledAt')
            ->with(null)
            ->willReturnSelf()
        ;

        $dbSubscription->expects($this->once())
            ->method('setEndDate')
            ->willReturnSelf()
        ;

        $this->planRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripePriceId' => 'price_123'])
            ->willReturn($plan)
        ;

        $dbSubscription->expects($this->once())
            ->method('setPlan')
            ->with($plan)
            ->willReturnSelf()
        ;

        $this->subscriptionRepository->expects($this->once())
            ->method('save')
            ->with($dbSubscription, true)
        ;

        $response = $this->webhookManager->handleSubscriptionUpdatedEvent($event);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Subscription update handled', $response->getContent());
    }

    public function testHandleSubscriptionUpdatedSubscriptionNotFound(): void
    {
        $event = new Event();

        $subscriptionData = [
            'id' => 'sub_123',
            'status' => 'active',
        ];
        $subscription = StripeSubscription::constructFrom($subscriptionData);

        $event->data = new StripeObject();
        $event->data->object = $subscription;

        $this->subscriptionRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeSubscriptionId' => 'sub_123'])
            ->willReturn(null)
        ;

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Subscription not found in database for update event', $this->anything())
        ;

        $response = $this->webhookManager->handleSubscriptionUpdatedEvent($event);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Subscription update handled', $response->getContent());
    }

    public function testHandleSubscriptionUpdatedWithException(): void
    {
        $event = new Event();

        $subscriptionData = [
            'id' => 'sub_123',
            'status' => 'active',
        ];
        $subscription = StripeSubscription::constructFrom($subscriptionData);

        $event->data = new StripeObject();
        $event->data->object = $subscription;

        $dbSubscription = $this->createMock(Subscription::class);

        $this->subscriptionRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeSubscriptionId' => 'sub_123'])
            ->willReturn($dbSubscription)
        ;

        $dbSubscription->expects($this->once())
            ->method('setStatus')
            ->willThrowException(new Exception('Test exception'))
        ;

        $this->logger->expects($this->atLeastOnce())
            ->method('error')
        ;

        $response = $this->webhookManager->handleSubscriptionUpdatedEvent($event);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('Failed to handle subscription update: Test exception', $response->getContent());
    }

    public function testHandleSubscriptionUpdatedWithCanceledStatus(): void
    {
        $event = new Event();

        $subscriptionData = [
            'id' => 'sub_123',
            'status' => 'canceled',
            'current_period_end' => time() + 86400, // +24h
            'cancel_at_period_end' => false,
        ];
        $subscription = StripeSubscription::constructFrom($subscriptionData);

        $event->data = new StripeObject();
        $event->data->object = $subscription;

        $dbSubscription = $this->createMock(Subscription::class);

        $this->subscriptionRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeSubscriptionId' => 'sub_123'])
            ->willReturn($dbSubscription)
        ;

        $dbSubscription->expects($this->once())
            ->method('setStatus')
            ->with('canceled')
            ->willReturnSelf()
        ;

        $canceledAtCallCount = 0;
        $dbSubscription->expects($this->exactly(2))
            ->method('setCanceledAt')
            ->willReturnCallback(function ($value) use (&$canceledAtCallCount) {
                if (0 === $canceledAtCallCount) {
                    $this->assertInstanceOf(\DateTimeImmutable::class, $value);
                } else {
                    $this->assertNull($value);
                }
                ++$canceledAtCallCount;

                return $this->createMock(Subscription::class);
            })
        ;

        $dbSubscription->expects($this->once())
            ->method('setEndDate')
            ->willReturnSelf()
        ;

        $this->subscriptionRepository->expects($this->once())
            ->method('save')
            ->with($dbSubscription, true)
        ;

        $response = $this->webhookManager->handleSubscriptionUpdatedEvent($event);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Subscription update handled', $response->getContent());
    }

    public function testHandleSubscriptionCanceledEventInvalidSubscription(): void
    {
        $event = new Event();

        $event->data = new StripeObject();
        $event->data->object = new StripeObject();

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Invalid subscription object')
        ;

        $response = $this->webhookManager->handleSubscriptionCanceledEvent($event);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid subscription object', $response->getContent());
    }

    public function testHandleSubscriptionCanceledEventSuccess(): void
    {
        $event = new Event();

        $subscriptionData = [
            'id' => 'sub_123',
            'status' => 'canceled',
            'current_period_end' => time() + 86400, // +24h
        ];
        $subscription = StripeSubscription::constructFrom($subscriptionData);

        $event->data = new StripeObject();
        $event->data->object = $subscription;

        $dbSubscription = $this->createMock(Subscription::class);

        $this->subscriptionRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeSubscriptionId' => 'sub_123'])
            ->willReturn($dbSubscription)
        ;

        $dbSubscription->expects($this->once())
            ->method('setCanceledAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturnSelf()
        ;

        $dbSubscription->expects($this->once())
            ->method('setStatus')
            ->with('canceled')
            ->willReturnSelf()
        ;

        $dbSubscription->expects($this->once())
            ->method('setEndDate')
            ->with($this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturnSelf()
        ;

        $this->subscriptionRepository->expects($this->once())
            ->method('save')
            ->with($dbSubscription, true)
        ;

        $response = $this->webhookManager->handleSubscriptionCanceledEvent($event);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Subscription cancellation handled', $response->getContent());
    }

    public function testHandleSubscriptionCanceledEventWithCancelAtPeriodEnd(): void
    {
        $event = new Event();

        $currentPeriodEnd = time() + 86400; // +24h
        $subscriptionData = [
            'id' => 'sub_123',
            'status' => 'active',
            'cancel_at_period_end' => true,
            'current_period_end' => $currentPeriodEnd,
        ];
        $subscription = StripeSubscription::constructFrom($subscriptionData);

        $event->data = new StripeObject();
        $event->data->object = $subscription;

        $dbSubscription = $this->createMock(Subscription::class);

        $this->subscriptionRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeSubscriptionId' => 'sub_123'])
            ->willReturn($dbSubscription)
        ;

        $dbSubscription->expects($this->once())
            ->method('setCanceledAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturnSelf()
        ;

        $dbSubscription->expects($this->once())
            ->method('setStatus')
            ->with('canceled')
            ->willReturnSelf()
        ;

        $dbSubscription->expects($this->once())
            ->method('setEndDate')
            ->with($this->callback(function ($date) use ($currentPeriodEnd) {
                return $date instanceof \DateTimeImmutable && abs($date->getTimestamp() - $currentPeriodEnd) < 2;
            }))
            ->willReturnSelf()
        ;

        $this->subscriptionRepository->expects($this->once())
            ->method('save')
            ->with($dbSubscription, true)
        ;

        $response = $this->webhookManager->handleSubscriptionCanceledEvent($event);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Subscription cancellation handled', $response->getContent());
    }

    public function testHandleSubscriptionCanceledSubscriptionNotFound(): void
    {
        $event = new Event();

        $subscriptionData = [
            'id' => 'sub_123',
            'status' => 'canceled',
        ];
        $subscription = StripeSubscription::constructFrom($subscriptionData);

        $event->data = new StripeObject();
        $event->data->object = $subscription;

        $this->subscriptionRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeSubscriptionId' => 'sub_123'])
            ->willReturn(null)
        ;

        $response = $this->webhookManager->handleSubscriptionCanceledEvent($event);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('Failed to handle subscription cancellation: Subscription not found', $response->getContent());
    }

    public function testHandleSubscriptionCanceledWithException(): void
    {
        $event = new Event();

        $subscriptionData = [
            'id' => 'sub_123',
            'status' => 'canceled',
        ];
        $subscription = StripeSubscription::constructFrom($subscriptionData);

        $event->data = new StripeObject();
        $event->data->object = $subscription;

        $dbSubscription = $this->createMock(Subscription::class);

        $this->subscriptionRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['stripeSubscriptionId' => 'sub_123'])
            ->willReturn($dbSubscription)
        ;

        $dbSubscription->expects($this->once())
            ->method('setCanceledAt')
            ->willThrowException(new Exception('Test exception'))
        ;

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to handle subscription cancellation: Test exception')
        ;

        $response = $this->webhookManager->handleSubscriptionCanceledEvent($event);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('Failed to handle subscription cancellation: Test exception', $response->getContent());
    }
}
