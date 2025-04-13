<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
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
        string $stripeSecretKey,
        private string $stripeWebhookSecret,
    ) {
        Stripe::setApiKey($stripeSecretKey);
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
            $event = Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
        } catch (Exception $e) {
            return new Response('Invalid signature: '.$e->getMessage(), 400);
        }

        if ('checkout.session.completed' === $event->type) {
            $session = $event->data->object;

            /** @var string|null $email */
            $email = $session->customer_email ?? null;

            /** @var string|null $stripeSubscriptionId */
            $stripeSubscriptionId = $session->subscription ?? null;

            $sessionId = $session->id ?? null;

            if (null === $email || null === $stripeSubscriptionId || null === $sessionId) {
                return new Response('Missing required data: email, subscription ID, or session ID', 400);
            }

            $user = $this->userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                return new Response('User not found', 404);
            }

            $lineItems = Session::allLineItems($sessionId);

            if (empty($lineItems->data)) {
                return new Response('No line items found', 400);
            }

            $priceId = $lineItems->data[0]->price->id ?? null;

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
        }

        return new Response('Webhook handled', 200);
    }
}
