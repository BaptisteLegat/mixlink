<?php

namespace App\Controller;

use App\Entity\User;
use App\Provider\ProviderManager;
use App\Session\SessionManager;
use App\Voter\AuthenticationVoter;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/mercure', name: 'api_mercure_')]
class MercureController extends AbstractController
{
    public function __construct(
        private SessionManager $sessionManager,
        private ProviderManager $providerManager,
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
    public function generateToken(string $sessionCode, Request $request): JsonResponse
    {
        $session = $this->sessionManager->findSessionByCode($sessionCode);
        if (!$session) {
            return new JsonResponse(['error' => 'Session not found'], 404);
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('aVerySecretKey!ForMercureJWT123456789')
        );

        $token = $config->builder()
            ->withClaim('mercure', [
                'subscribe' => ["session/{$sessionCode}"],
            ])
            ->getToken($config->signer(), $config->signingKey());

        return new JsonResponse([
            'token' => $token->toString(),
            'mercureUrl' => 'http://localhost:3001/.well-known/mercure',
        ]);
    }

    #[Route('/auth/host/{sessionCode}', name: 'auth_host', methods: ['GET'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED)]
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
            return new JsonResponse(['error' => 'Session not found'], 404);
        }

        if ($session->getHost()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Not the session host'], 403);
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('aVerySecretKey!ForMercureJWT123456789')
        );

        $token = $config->builder()
            ->withClaim('mercure', [
                'subscribe' => ["session/{$sessionCode}"],
                'publish' => ["session/{$sessionCode}"],
            ])
            ->getToken($config->signer(), $config->signingKey())
        ;

        return new JsonResponse([
            'token' => $token->toString(),
            'mercureUrl' => 'http://localhost:3001/.well-known/mercure',
        ]);
    }
}
