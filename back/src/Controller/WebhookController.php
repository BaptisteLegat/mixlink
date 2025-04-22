<?php

namespace App\Controller;

use App\Service\StripeService;
use App\Webhook\WebhookManager;
use Exception;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private WebhookManager $webhookManager,
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
            return new Response('Invalid signature: '.$e->getMessage(), 400);
        }

        if ('checkout.session.completed' === $event->type && $event->data->object instanceof Session) {
            return $this->webhookManager->handleCheckoutSessionCompleted($event->data->object);
        }

        return new Response('Webhook handled', 200);
    }
}
