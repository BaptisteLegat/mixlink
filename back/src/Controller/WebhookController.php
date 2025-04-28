<?php

namespace App\Controller;

use App\Service\StripeService;
use App\Webhook\WebhookManager;
use Exception;
use Psr\Log\LoggerInterface;
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

        if ('checkout.session.completed' === $event->type) {
            $session = $event->data->object;

            if (!$session instanceof Session) {
                $this->logger->error('Invalid session object');

                return new Response('Invalid session object', 400);
            }

            return $this->webhookManager->handleCheckoutSessionCompleted($session);
        }

        return new Response('Webhook handled', 200);
    }
}
