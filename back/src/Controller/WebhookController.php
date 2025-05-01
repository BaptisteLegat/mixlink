<?php

namespace App\Controller;

use App\Service\StripeService;
use App\Webhook\WebhookManager;
use Exception;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Subscription as StripeSubscription;
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
            return new Response('Missing signature', 400);
        }

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);
        } catch (Exception $e) {
            $this->logger->error('Invalid signature: '.$e->getMessage());

            return new Response('Invalid signature: '.$e->getMessage(), 400);
        }

        $response = match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
            'customer.subscription.deleted' => $this->handleSubscriptionCanceled($event),
            'customer.subscription.created' => $this->handleSubscriptionUpdated($event),
            'customer.subscription.paused' => $this->handleSubscriptionUpdated($event),
            'customer.subscription.resumed' => $this->handleSubscriptionUpdated($event),
            'customer.subscription.trial_will_end' => $this->handleSubscriptionUpdated($event),
            default => new Response('Unhandled webhook event: '.$event->type.' - '.$event->id, 200),
        };

        return $response;
    }

    private function handleCheckoutSessionCompleted(Event $event): Response
    {
        $session = $event->data->object;

        if (!$session instanceof Session) {
            $this->logger->error('Invalid session object');

            return new Response('Invalid session object', 400);
        }

        return $this->webhookManager->handleCheckoutSessionCompleted($session);
    }

    private function handleSubscriptionUpdated(Event $event): Response
    {
        $subscription = $event->data->object;

        if (!$subscription instanceof StripeSubscription) {
            $this->logger->error('Invalid subscription object');

            return new Response('Invalid subscription object', 400);
        }

        try {
            $this->webhookManager->handleSubscriptionUpdated($subscription);

            return new Response('Subscription update handled', 200);
        } catch (Exception $e) {
            $this->logger->error('Failed to handle subscription update: '.$e->getMessage());

            return new Response('Failed to handle subscription update', 500);
        }
    }

    private function handleSubscriptionCanceled(Event $event): Response
    {
        $subscription = $event->data->object;

        if (!$subscription instanceof StripeSubscription) {
            $this->logger->error('Invalid subscription object');

            return new Response('Invalid subscription object', 400);
        }

        try {
            $this->webhookManager->handleSubscriptionCanceled($subscription);

            return new Response('Subscription cancellation handled', 200);
        } catch (Exception $e) {
            $this->logger->error('Failed to handle subscription cancellation: '.$e->getMessage());

            return new Response('Failed to handle subscription cancellation', 500);
        }
    }
}
