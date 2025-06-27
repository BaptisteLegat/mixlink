<?php

namespace App\Controller;

use App\Entity\User;
use App\Provider\ProviderManager;
use App\Repository\PlanRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
use App\Voter\AuthenticationVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SubscriptionController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private ProviderManager $providerManager,
        private SubscriptionManager $subscriptionManager,
    ) {
    }

    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED)]
    #[Route('/api/subscribe/{planName}', name: 'api_subscription_start', methods: ['GET'])]
    public function subscribe(
        string $planName,
        PlanRepository $planRepository,
        Request $request,
    ): JsonResponse {
        /** @var string $accessToken */
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        /** @var User $user */
        $user = $this->providerManager->findByAccessToken($accessToken);

        $plan = $planRepository->findOneBy(['name' => $planName]);
        if (null === $plan) {
            return new JsonResponse(['error' => 'Plan not found.'], 404);
        }

        $priceId = $this->stripeService->getPriceIdForPlan($plan->getName());

        if (null === $priceId) {
            return new JsonResponse(['error' => 'Stripe price ID not configured for this plan.'], 400);
        }

        /** @var string $frontendUrl */
        $frontendUrl = $_ENV['FRONTEND_URL'];
        $successUrl = $frontendUrl.'/profile';

        $checkoutSession = $this->stripeService->createCheckoutSession(
            $priceId,
            $successUrl,
            $frontendUrl,
            $user->getEmail()
        );

        $checkoutUrl = $checkoutSession->url;
        if (null === $checkoutUrl) {
            return new JsonResponse(['error' => 'Unable to create checkout session.'], 500);
        }

        return new JsonResponse([
            'url' => $checkoutUrl,
        ]);
    }

    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED)]
    #[Route('/api/subscription/cancel', name: 'api_subscription_cancel', methods: ['POST'])]
    public function cancelSubscription(Request $request): JsonResponse
    {
        /** @var string $accessToken */
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        /** @var User $user */
        $user = $this->providerManager->findByAccessToken($accessToken);

        $subscription = $user->getSubscription();
        if (null === $subscription || null === $subscription->getStripeSubscriptionId()) {
            return new JsonResponse(['error' => 'No active subscription found'], 404);
        }

        $result = $this->subscriptionManager->cancelSubscription($user);

        if (!$result) {
            return new JsonResponse(['error' => 'Failed to cancel subscription'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Subscription successfully cancelled',
        ]);
    }

    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED)]
    #[Route('/api/subscription/change/{planName}', name: 'api_subscription_change', methods: ['POST'])]
    public function changeSubscription(
        string $planName,
        Request $request,
        PlanRepository $planRepository,
    ): JsonResponse {
        /** @var string $accessToken */
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        /** @var User $user */
        $user = $this->providerManager->findByAccessToken($accessToken);

        $subscription = $user->getSubscription();
        if (null === $subscription || null === $subscription->getStripeSubscriptionId()) {
            return new JsonResponse(['error' => 'No active subscription found'], 404);
        }

        $newPlan = $planRepository->findOneBy(['name' => $planName]);
        if (null === $newPlan) {
            return new JsonResponse(['error' => 'Plan not found'], 404);
        }

        $currentPlan = $subscription->getPlan();
        if (null !== $currentPlan && $currentPlan->getId() === $newPlan->getId()) {
            return new JsonResponse(['error' => 'Already subscribed to this plan'], 400);
        }

        $result = $this->subscriptionManager->changeSubscriptionPlan($user, $newPlan);

        if (!$result) {
            return new JsonResponse(['error' => 'Failed to change subscription'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Subscription successfully changed',
        ]);
    }
}
