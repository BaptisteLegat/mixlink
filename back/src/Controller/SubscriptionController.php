<?php

namespace App\Controller;

use App\Provider\ProviderManager;
use App\Repository\PlanRepository;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SubscriptionController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private ProviderManager $providerManager,
    ) {
    }

    #[Route('/api/subscribe/{planName}', name: 'api_subscription_start', methods: ['GET'])]
    public function subscribe(
        string $planName,
        PlanRepository $planRepository,
        Request $request,
    ): RedirectResponse|JsonResponse {
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        if (null === $accessToken) {
            return new JsonResponse(['error' => 'Unauthorized.'], 401);
        }

        $user = $this->providerManager->findByAccessToken($accessToken);
        if (null === $user) {
            return new JsonResponse(['error' => 'Invalid or expired token.'], 401);
        }

        $plan = $planRepository->findOneBy(['name' => $planName]);
        if (!$plan) {
            return new JsonResponse(['error' => 'Plan not found.'], 404);
        }

        $priceId = $this->stripeService->getPriceIdForPlan($plan->getName());
        if (null === $priceId) {
            return new JsonResponse(['error' => 'Stripe price ID not configured for this plan.'], 400);
        }

        $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';
        $successUrl = $frontendUrl.'/abonnement/success';
        $cancelUrl = $frontendUrl.'/abonnement/cancel';

        $checkoutSession = $this->stripeService->createCheckoutSession(
            $priceId,
            $successUrl,
            $cancelUrl,
            $user->getEmail()
        );

        $checkoutUrl = $checkoutSession->url;
        if (null === $checkoutUrl) {
            return new JsonResponse(['error' => 'Unable to create checkout session.'], 500);
        }

        return $this->redirect($checkoutUrl);
    }
}
