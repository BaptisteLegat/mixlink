<?php

namespace App\Controller;

use App\Entity\User;
use App\Mercure\MercureManager;
use App\Provider\ProviderManager;
use App\Session\Manager\SessionManager;
use App\Voter\AuthenticationVoter;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/mercure', name: 'api_mercure_')]
#[OA\Tag(name: 'Mercure', description: 'Mercure endpoints')]
class MercureController extends AbstractController
{
    public function __construct(
        private SessionManager $sessionManager,
        private ProviderManager $providerManager,
        private MercureManager $mercureManager,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/auth/{sessionCode}', name: 'auth', methods: ['GET'])]
    #[OA\Get(
        path: '/api/mercure/auth/{sessionCode}',
        summary: 'Get Mercure JWT token for session',
        description: 'Generate a JWT token for accessing Mercure updates for a specific session',
        tags: ['Mercure'],
        parameters: [
            new OA\Parameter(
                name: 'sessionCode',
                in: 'path',
                required: true,
                description: 'Session code',
                schema: new OA\Schema(type: 'string', example: 'ABC12345')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'JWT token generated successfully',
                content: new OA\JsonContent(
                    properties: [
                        'token' => new OA\Property(
                            property: 'token',
                            type: 'string',
                            description: 'JWT token for Mercure'
                        ),
                        'mercureUrl' => new OA\Property(
                            property: 'mercureUrl',
                            type: 'string',
                            description: 'Mercure endpoint URL'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Session not found'
            ),
        ]
    )]
    public function generateToken(string $sessionCode): JsonResponse
    {
        $session = $this->sessionManager->findSessionByCode($sessionCode);
        if (!$session) {
            return new JsonResponse(['error' => 'session.create.error_session_not_found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $this->mercureManager->generateTokenForSession($sessionCode);

            return new JsonResponse($result);
        } catch (RuntimeException $e) {
            $this->logger->error('Error generating Mercure token for session', [
                'sessionCode' => $sessionCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'common.error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/auth/host/{sessionCode}', name: 'auth_host', methods: ['GET'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
    #[OA\Get(
        path: '/api/mercure/auth/host/{sessionCode}',
        summary: 'Get Mercure JWT token for session host',
        description: 'Generate a JWT token with publish rights for session host',
        tags: ['Mercure'],
        parameters: [
            new OA\Parameter(
                name: 'sessionCode',
                in: 'path',
                required: true,
                description: 'Session code',
                schema: new OA\Schema(type: 'string', example: 'ABC12345')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'JWT token generated successfully',
                content: new OA\JsonContent(
                    properties: [
                        'token' => new OA\Property(
                            property: 'token',
                            type: 'string',
                            description: 'JWT token for Mercure'
                        ),
                        'mercureUrl' => new OA\Property(
                            property: 'mercureUrl',
                            type: 'string',
                            description: 'Mercure endpoint URL'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Not the session host'
            ),
            new OA\Response(
                response: 404,
                description: 'Session not found'
            ),
        ]
    )]
    public function generateHostToken(string $sessionCode, Request $request): JsonResponse
    {
        /** @var string $accessToken */
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        /** @var User $user */
        $user = $this->providerManager->findByAccessToken($accessToken);

        $session = $this->sessionManager->findSessionByCode($sessionCode);
        if (!$session) {
            return new JsonResponse(['error' => 'session.create.error_session_not_found'], Response::HTTP_NOT_FOUND);
        }

        if ($session->getHost()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'session.create.error_not_host'], Response::HTTP_FORBIDDEN);
        }

        try {
            $result = $this->mercureManager->generateTokenForHost($sessionCode);

            return new JsonResponse($result);
        } catch (RuntimeException $e) {
            $this->logger->error('Error generating Mercure token for session host', [
                'sessionCode' => $sessionCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'common.error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
