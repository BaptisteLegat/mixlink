<?php

namespace App\Controller;

use App\Entity\User;
use App\Provider\ProviderManager;
use App\Repository\PlanRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
use App\Voter\AuthenticationVoter;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
#[OA\Tag(name: 'Subscription', description: 'Subscription management endpoints')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private ProviderManager $providerManager,
        private SubscriptionManager $subscriptionManager,
    ) {
    }

    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED)]
    #[Route('/subscribe/{planName}', name: 'api_subscription_start', methods: ['GET'])]
    #[OA\Get(
        path: '/api/subscribe/{planName}',
        summary: 'Start a new subscription',
        description: 'Creates a Stripe checkout session for the specified plan',
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(
                name: 'planName',
                in: 'path',
                required: true,
                description: 'Name of the plan to subscribe to',
                schema: new OA\Schema(type: 'string', example: 'premium')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Checkout session created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'url', type: 'string', description: 'Stripe checkout URL', example: 'https://checkout.stripe.com/pay/cs_test_...'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid plan or configuration error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Plan not found.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Plan not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Plan not found.'),
                    ]
                )
            ),
        ]
    )]
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
    #[Route('/subscription/cancel', name: 'api_subscription_cancel', methods: ['POST'])]
    #[OA\Post(
        path: '/api/subscription/cancel',
        summary: 'Cancel current subscription',
        description: 'Cancels the user\'s active subscription',
        tags: ['Subscription'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscription cancelled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Subscription successfully cancelled'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'No active subscription found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'No active subscription found'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to cancel subscription',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Failed to cancel subscription'),
                    ]
                )
            ),
        ]
    )]
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
    #[Route('/subscription/change/{planName}', name: 'api_subscription_change', methods: ['POST'])]
    #[OA\Post(
        path: '/api/subscription/change/{planName}',
        summary: 'Change subscription plan',
        description: 'Changes the user\'s subscription to a different plan',
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(
                name: 'planName',
                in: 'path',
                required: true,
                description: 'Name of the new plan',
                schema: new OA\Schema(type: 'string', example: 'premium')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscription changed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Subscription successfully changed'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Already subscribed to this plan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Already subscribed to this plan'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Plan not found or no active subscription',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Plan not found'),
                    ]
                )
            ),
        ]
    )]
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
