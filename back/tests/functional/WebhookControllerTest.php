<?php

namespace App\Tests\Functional;

use App\Service\StripeService;
use App\Webhook\WebhookManager;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Stripe\Checkout\Session;
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

    public function testHandleStripeWebhookWithCheckoutSessionCompleted(): void
    {
        $sessionMock = $this->createMock(Session::class);
        $eventMock = new Event();
        $eventMock->type = 'checkout.session.completed';
        $eventMock->data = new stdClass();
        $eventMock->data->object = $sessionMock;

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('constructWebhookEvent')
            ->willReturn($eventMock)
        ;

        $this->webhookManagerMock
            ->expects($this->once())
            ->method('handleCheckoutSessionCompleted')
            ->with($sessionMock)
            ->willReturn(new Response('Checkout session handled', Response::HTTP_OK))
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
    }

    public function testHandleStripeWebhookWithOtherEventType(): void
    {
        $eventMock = new Event();
        $eventMock->type = 'other.event.type';

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

    public function testHandleStripeWebhookWithInvalidSessionObject(): void
    {
        $eventMock = new Event();
        $eventMock->type = 'checkout.session.completed';
        $eventMock->data = new stdClass();
        $eventMock->data->object = new stdClass(); // Pas une instance de Session

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

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Invalid session object', $this->client->getResponse()->getContent());
    }
}
