<?php

namespace App\Controller;

use App\Entity\User;
use App\Provider\ProviderManager;
use App\Repository\PlanRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
use App\Voter\AuthenticationVoter;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/subscribe/{planName}', name: 'api_subscription_start', methods: ['GET'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
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
            return new JsonResponse(['error' => 'subscription.start.error_plan_not_found'], Response::HTTP_NOT_FOUND);
        }

        $priceId = $this->stripeService->getPriceIdForPlan($plan->getName());

        if (null === $priceId) {
            return new JsonResponse(['error' => 'subscription.start.error_stripe_price_id_not_configured'], Response::HTTP_BAD_REQUEST);
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
            return new JsonResponse(['error' => 'subscription.start.error_unable_to_create_checkout_session'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'url' => $checkoutUrl,
        ]);
    }

    #[Route('/subscription/cancel', name: 'api_subscription_cancel', methods: ['POST'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
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
            return new JsonResponse(['error' => 'subscription.cancel.error_no_active_subscription'], Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $this->subscriptionManager->cancelSubscription($user);
            if (!$result) {
                throw new Exception('Failed to cancel subscription');
            }
        } catch (Exception $e) {
            $this->logger->error('Error cancelling subscription', [
                'userId' => $user->getId(),
                'subscription' => $subscription,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'subscription.cancel.error_failed_to_cancel_subscription'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/subscription/change/{planName}', name: 'api_subscription_change', methods: ['POST'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
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
            return new JsonResponse(['error' => 'subscription.change.error_no_active_subscription'], Response::HTTP_NOT_FOUND);
        }

        $newPlan = $planRepository->findOneBy(['name' => $planName]);
        if (null === $newPlan) {
            return new JsonResponse(['error' => 'subscription.change.error_plan_not_found'], Response::HTTP_NOT_FOUND);
        }

        $currentPlan = $subscription->getPlan();
        if (null !== $currentPlan && $currentPlan->getId() === $newPlan->getId()) {
            return new JsonResponse(['error' => 'subscription.change.error_already_subscribed_to_this_plan'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->subscriptionManager->changeSubscriptionPlan($user, $newPlan);
            if (!$result) {
                throw new Exception('Failed to change subscription plan');
            }
        } catch (Exception $e) {
            $this->logger->error('Error changing subscription plan', [
                'userId' => $user->getId(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'subscription.change.error_failed_to_change_subscription'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => true]);
    }
}
