<?php

namespace App\Controller;

use App\Entity\User;
use App\Provider\ProviderManager;
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
#[OA\Tag(name: 'Provider', description: 'OAuth provider management endpoints')]
class ProviderController extends AbstractController
{
    public function __construct(
        private ProviderManager $providerManager,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/provider/{id}/disconnect', name: 'api_provider_disconnect', methods: ['POST'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
    #[OA\Post(
        path: '/api/provider/{id}/disconnect',
        summary: 'Disconnect OAuth provider',
        description: 'Disconnects a specific OAuth provider from the user account',
        tags: ['Provider'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Provider ID',
                schema: new OA\Schema(type: 'string', example: '01234567-89ab-cdef-0123-456789abcdef')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Provider disconnected successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'mainProvider', type: 'boolean', description: 'Whether this was the main provider', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Provider successfully disconnected'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Provider not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Provider not found'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to disconnect provider',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Failed to disconnect provider'),
                    ]
                )
            ),
        ]
    )]
    public function disconnect(
        string $id,
        Request $request,
    ): JsonResponse {
        /** @var string $accessToken */
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        /** @var User $user */
        $user = $this->providerManager->findByAccessToken($accessToken);

        try {
            $provider = null;
            foreach ($user->getProviders() as $p) {
                if ((string) $p->getId() === $id) {
                    $provider = $p;
                    break;
                }
            }

            if (null === $provider) {
                return new JsonResponse(['error' => 'provider.disconnect.error_provider_not_found'], Response::HTTP_NOT_FOUND);
            }

            $isMainProvider = $provider->getAccessToken() === $accessToken;

            $this->providerManager->disconnectProvider($id, $user);

            if ($isMainProvider) {
                $response = new JsonResponse([
                    'success' => true,
                    'mainProvider' => true,
                    'message' => 'provider.disconnect.success',
                ]);
                $response->headers->clearCookie('AUTH_TOKEN');

                return $response;
            }

            return new JsonResponse([
                'success' => true,
                'mainProvider' => false,
                'message' => 'provider.disconnect.success',
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to disconnect provider', [
                'providerId' => $id,
                'userId' => $user->getId(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'common.error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
