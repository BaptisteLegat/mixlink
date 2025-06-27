<?php

namespace App\Tests\Functional;

use App\Service\StripeService;
use App\Webhook\WebhookManager;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Stripe\Event;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class WebhookControllerTest extends WebTestCase
{
    private MockObject $stripeServiceMock;
    private MockObject $webhookManagerMock;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $this->stripeServiceMock = $this->createMock(StripeService::class);
        $this->webhookManagerMock = $this->createMock(WebhookManager::class);

        $container = $this->client->getContainer();
        $container->set(StripeService::class, $this->stripeServiceMock);
        $container->set(WebhookManager::class, $this->webhookManagerMock);
    }

    public function testHandleStripeWebhookWithMissingSignature(): void
    {
        $this->client->request(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            [],
            json_encode(['payload' => 'test'])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Missing signature', $this->client->getResponse()->getContent());
    }

    public function testHandleStripeWebhookWithInvalidSignature(): void
    {
        $this->stripeServiceMock
            ->expects($this->once())
            ->method('constructWebhookEvent')
            ->willThrowException(new Exception('Invalid signature test'))
        ;

        $this->client->request(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 'test_signature'],
            json_encode(['payload' => 'test'])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Invalid signature: Invalid signature test', $this->client->getResponse()->getContent());
    }

    public function testHandleStripeWebhookWithOtherEventType(): void
    {
        $eventMock = $this->createMock(Event::class);
        $eventMock->method('__get')->willReturnMap([
            ['type', 'other.event.type'],
            ['id', 'evt_123456'],
        ]);

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('constructWebhookEvent')
            ->willReturn($eventMock)
        ;

        $this->webhookManagerMock
            ->expects($this->never())
            ->method('handleCheckoutSessionCompleted')
        ;

        $this->client->request(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 'test_signature'],
            json_encode(['payload' => 'test'])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Webhook handled', $this->client->getResponse()->getContent());
    }

    public function testHandleStripeWebhookWithCheckoutSessionCompletedEvent(): void
    {
        $eventMock = $this->createMock(Event::class);
        $eventMock->method('__get')->willReturnMap([
            ['type', WebhookManager::EVENT_CHECKOUT_SESSION_COMPLETED],
            ['id', 'evt_123456'],
        ]);

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('constructWebhookEvent')
            ->willReturn($eventMock)
        ;

        $this->webhookManagerMock
            ->expects($this->once())
            ->method('handleCheckoutSessionCompletedEvent')
            ->with($eventMock)
            ->willReturn(new Response('Checkout session completed', Response::HTTP_OK))
        ;

        $this->client->request(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 'test_signature'],
            json_encode(['payload' => 'test'])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Checkout session completed', $this->client->getResponse()->getContent());
    }

    public function testHandleStripeWebhookWithSubscriptionUpdatedEvent(): void
    {
        $eventMock = $this->createMock(Event::class);
        $eventMock->method('__get')->willReturnMap([
            ['type', WebhookManager::EVENT_SUBSCRIPTION_UPDATED],
            ['id', 'evt_123456'],
        ]);

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('constructWebhookEvent')
            ->willReturn($eventMock)
        ;

        $this->webhookManagerMock
            ->expects($this->once())
            ->method('handleSubscriptionUpdatedEvent')
            ->with($eventMock)
            ->willReturn(new Response('Subscription updated', Response::HTTP_OK))
        ;

        $this->client->request(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 'test_signature'],
            json_encode(['payload' => 'test'])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Subscription updated', $this->client->getResponse()->getContent());
    }

    public function testHandleStripeWebhookWithSubscriptionDeletedEvent(): void
    {
        $eventMock = $this->createMock(Event::class);
        $eventMock->method('__get')->willReturnMap([
            ['type', WebhookManager::EVENT_SUBSCRIPTION_DELETED],
            ['id', 'evt_123456'],
        ]);

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('constructWebhookEvent')
            ->willReturn($eventMock)
        ;

        $this->webhookManagerMock
            ->expects($this->once())
            ->method('handleSubscriptionCanceledEvent')
            ->with($eventMock)
            ->willReturn(new Response('Subscription canceled', Response::HTTP_OK))
        ;

        $this->client->request(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 'test_signature'],
            json_encode(['payload' => 'test'])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Subscription canceled', $this->client->getResponse()->getContent());
    }

    public function testHandleStripeWebhookWithExceptionDuringProcessing(): void
    {
        $eventMock = $this->createMock(Event::class);
        $eventMock->method('__get')->willReturnMap([
            ['type', WebhookManager::EVENT_CHECKOUT_SESSION_COMPLETED],
            ['id', 'evt_123456'],
        ]);

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('constructWebhookEvent')
            ->willReturn($eventMock)
        ;

        $this->webhookManagerMock
            ->expects($this->once())
            ->method('handleCheckoutSessionCompletedEvent')
            ->with($eventMock)
            ->willThrowException(new Exception('Processing error'))
        ;

        $this->client->request(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 'test_signature'],
            json_encode(['payload' => 'test'])
        );

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Error processing webhook: Processing error', $this->client->getResponse()->getContent());
    }
}
