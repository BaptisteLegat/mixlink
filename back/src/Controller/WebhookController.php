<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use App\Service\StripeService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PlanRepository $planRepository,
        private UserRepository $userRepository,
        private StripeService $stripeService,
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
            /** @var Event $event */
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);
        } catch (Exception $e) {
            return new Response('Invalid signature: '.$e->getMessage(), 400);
        }

        if ('checkout.session.completed' === $event->type && $event->data->object instanceof Session) {
            return $this->handleCheckoutSessionCompleted($event->data->object);
        }

        return new Response('Webhook handled', 200);
    }

    /**
     * Handle checkout.session.completed events.
     */
    private function handleCheckoutSessionCompleted(Session $session): Response
    {
        /** @var string|null $email */
        $email = $session->customer_email ?? null;

        /** @var string|null $stripeSubscriptionId */
        $stripeSubscriptionId = $session->subscription ?? null;

        // Session id is never null when working with a valid Session object
        $sessionId = $session->id;

        if (null === $email || null === $stripeSubscriptionId) {
            return new Response('Missing required data: email or subscription ID', 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return new Response('User not found', 404);
        }

        $priceId = $this->getPriceIdFromSession($sessionId);
        if (null === $priceId) {
            return new Response('Price ID not found', 400);
        }

        $plan = $this->planRepository->findOneBy(['stripePriceId' => $priceId]);
        if (!$plan) {
            return new Response('Plan not found', 404);
        }

        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setPlan($plan);
        $subscription->setStripeSubscriptionId($stripeSubscriptionId);
        $subscription->setStartDate(new DateTimeImmutable());
        $subscription->setEndDate((new DateTimeImmutable())->modify('+1 month'));

        $this->em->persist($subscription);
        $this->em->flush();

        return new Response('Webhook handled', 200);
    }

    /**
     * Extract price ID from session line items.
     */
    private function getPriceIdFromSession(string $sessionId): ?string
    {
        /** @var StripeObject $lineItems */
        $lineItems = $this->stripeService->getSessionLineItems($sessionId);

        /** @var array<int, StripeObject> $lineItemsData */
        $lineItemsData = $lineItems->data ?? [];

        if (empty($lineItemsData)) {
            return null;
        }

        /** @var StripeObject|null $lineItem */
        $lineItem = $lineItemsData[0] ?? null;
        if (!$lineItem) {
            return null;
        }

        /** @var StripeObject|null $price */
        $price = $lineItem->price ?? null;
        if (!$price || !isset($price->id)) {
            return null;
        }

        /** @var string */
        return $price->id;
    }
}
