<?php

namespace App\Controller;

use App\Service\StripeService;
use App\Webhook\WebhookManager;
use Exception;
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
