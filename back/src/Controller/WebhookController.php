<?php

namespace App\Controller;

use App\Service\StripeService;
use App\Webhook\WebhookManager;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private WebhookManager $webhookManager,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/api/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    #[OA\Post(
        path: '/api/webhook/stripe',
        summary: 'Handle Stripe webhook events',
        description: 'Processes Stripe webhook events for subscription management',
        tags: ['Webhooks'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Stripe webhook event payload',
            content: new OA\JsonContent(
                type: 'object',
                example: [
                    'id' => 'evt_1234567890',
                    'type' => 'checkout.session.completed',
                    'data' => [
                        'object' => [
                            'id' => 'cs_1234567890',
                            'subscription' => 'sub_1234567890',
                        ],
                    ],
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Webhook processed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Webhook handled'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request or missing signature',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Missing signature'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error processing webhook',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Error processing webhook: ...'),
                    ]
                )
            ),
        ]
    )]
    public function handleStripeWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        if (null === $signature) {
            return new Response('Missing signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);
        } catch (Exception $e) {
            $this->logger->error('Invalid signature: '.$e->getMessage());

            return new Response('Invalid signature: '.$e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        try {
            $response = match ($event->type) {
                WebhookManager::EVENT_CHECKOUT_SESSION_COMPLETED => $this->webhookManager->handleCheckoutSessionCompletedEvent($event),
                WebhookManager::EVENT_SUBSCRIPTION_UPDATED => $this->webhookManager->handleSubscriptionUpdatedEvent($event),
                WebhookManager::EVENT_SUBSCRIPTION_DELETED => $this->webhookManager->handleSubscriptionCanceledEvent($event),
                default => new Response('Webhook handled', Response::HTTP_OK),
            };

            return $response;
        } catch (Exception $e) {
            $this->logger->error('Error processing webhook: '.$e->getMessage(), [
                'event_type' => $event->type,
                'event_id' => $event->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return new Response('Error processing webhook: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
