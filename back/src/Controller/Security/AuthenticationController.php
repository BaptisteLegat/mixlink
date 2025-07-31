<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Provider\ProviderManager;
use App\Security\OAuthService;
use App\User\UserManager;
use App\Voter\AuthenticationVoter;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
#[OA\Tag(name: 'Authentication', description: 'OAuth authentication endpoints')]
class AuthenticationController extends AbstractController
{
    public function __construct(
        private OAuthService $oAuthService,
        private ProviderManager $providerManager,
        private UserManager $userManager,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/auth/{provider}', name: 'app_auth', methods: ['GET'], requirements: ['provider' => 'google|spotify|soundcloud'])]
    #[OA\Get(
        path: '/api/auth/{provider}',
        summary: 'Initiate OAuth authentication',
        description: 'Redirects to OAuth provider for authentication',
        tags: ['Authentication'],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                in: 'path',
                required: true,
                description: 'OAuth provider',
                schema: new OA\Schema(type: 'string', enum: ['google', 'spotify', 'soundcloud'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 302,
                description: 'Redirect to OAuth provider',
                headers: [
                    new OA\Header(header: 'Location', description: 'OAuth provider URL', schema: new OA\Schema(type: 'string')),
                ]
            ),
        ]
    )]
    public function connect(string $provider): RedirectResponse
    {
        return $this->oAuthService->getRedirectResponse($provider);
    }

    #[Route('/auth/{provider}/callback', name: 'app_auth_callback', methods: ['GET'], requirements: ['provider' => 'google|spotify|soundcloud'])]
    #[OA\Get(
        path: '/api/auth/{provider}/callback',
        summary: 'OAuth callback handler',
        description: 'Handles OAuth callback and creates user session',
        tags: ['Authentication'],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                in: 'path',
                required: true,
                description: 'OAuth provider',
                schema: new OA\Schema(type: 'string', enum: ['google', 'spotify', 'soundcloud'])
            ),
            new OA\Parameter(
                name: 'code',
                in: 'query',
                required: true,
                description: 'OAuth authorization code',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 302,
                description: 'Redirect to frontend with authentication cookie',
                headers: [
                    new OA\Header(header: 'Location', description: 'Frontend URL', schema: new OA\Schema(type: 'string')),
                    new OA\Header(header: 'Set-Cookie', description: 'Authentication token', schema: new OA\Schema(type: 'string')),
                ]
            ),
        ]
    )]
    public function connectCheck(string $provider): RedirectResponse
    {
        try {
            $oauthData = $this->oAuthService->fetchUser($provider);
            $user = $this->userManager->create($oauthData, $provider);
            $providerEntity = $user->getProviderByName($provider);

            $response = new RedirectResponse($_ENV['FRONTEND_URL']);

            $accessToken = $providerEntity ? $providerEntity->getAccessToken() : null;
            if (null !== $accessToken) {
                $cookie = Cookie::create('AUTH_TOKEN')
                    ->withValue($accessToken)
                    ->withHttpOnly(true)
                    ->withSecure(true)
                    ->withSameSite('strict')
                ;

                $response->headers->setCookie($cookie);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('Error during OAuth callback', [
                'provider' => $provider,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new RedirectResponse($_ENV['FRONTEND_URL']);
        }
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/me',
        summary: 'Get current user profile',
        description: 'Returns the authenticated user\'s profile information or empty object if not authenticated',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User profile data or empty object when not authenticated',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(ref: '#/components/schemas/UserModel'),
                        new OA\Schema(type: 'object', properties: []),
                    ]
                )
            ),
        ]
    )]
    public function getUserProfile(Request $request): JsonResponse
    {
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        if (null === $accessToken) {
            return new JsonResponse([]);
        }

        $user = $this->providerManager->findByAccessToken($accessToken);
        if (null === $user) {
            return new JsonResponse([]);
        }

        $userModel = $this->userManager->getUserModel($user, $accessToken);

        return new JsonResponse($userModel->toArray());
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout user',
        description: 'Clears the authentication cookie',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(type: 'object'),
                headers: [
                    new OA\Header(header: 'Set-Cookie', description: 'Clear authentication cookie', schema: new OA\Schema(type: 'string')),
                ]
            ),
        ]
    )]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse([]);
        $response->headers->clearCookie('AUTH_TOKEN');

        return $response;
    }

    #[Route('/me/delete', name: 'api_me_delete', methods: ['DELETE'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
    #[OA\Delete(
        path: '/api/me/delete',
        summary: 'Delete user account',
        description: 'Permanently deletes the authenticated user\'s account',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Unauthorized'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'User not found'),
                    ]
                )
            ),
        ]
    )]
    public function deleteAccount(Request $request): JsonResponse
    {
        /** @var string $accessToken */
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        /** @var User $user */
        $user = $this->providerManager->findByAccessToken($accessToken);

        try {
            $this->userManager->deleteUser($user);
        } catch (Exception $e) {
            $this->logger->error('Failed to delete user', [
                'userId' => $user->getId(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'profile.delete_account.error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = new JsonResponse(['success' => true]);
        $response->headers->clearCookie('AUTH_TOKEN');

        return $response;
    }

    #[Route('/me/email', name: 'api_me_set_email', methods: ['PATCH'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
    #[OA\Patch(
        path: '/api/me/email',
        summary: 'Define email for SoundCloud user',
        description: 'Allows a user logged in only via SoundCloud to enter their email address.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'user@email.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email updated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                ])
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'Invalid request'),
                ])
            ),
            new OA\Response(
                response: 403,
                description: 'Not allowed',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'Not allowed'),
                ])
            ),
        ]
    )]
    public function setEmailForSoundCloud(Request $request): JsonResponse
    {
        /** @var string $accessToken */
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        /** @var User $user */
        $user = $this->providerManager->findByAccessToken($accessToken);

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'profile.email.invalid'], Response::HTTP_BAD_REQUEST);
        }

        $email = isset($data['email']) && is_string($data['email']) ? $data['email'] : null;
        if (null === $email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'profile.email.invalid'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->userManager->updateEmailForSoundCloudUser($user, $email);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'profile.email.not_soundcloud_only'], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            $this->logger->error('Error updating SoundCloud email', [
                'userId' => $user->getId(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'profile.email.update_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => true]);
    }
}
