<?php

namespace App\Controller;

use App\Entity\Plan;
use App\Entity\Playlist;
use App\Entity\User;
use App\Provider\ProviderManager;
use App\Repository\PlaylistRepository;
use App\Service\Export\Model\ExportResult;
use App\Service\PlaylistExportService;
use App\Voter\AuthenticationVoter;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/playlist')]
#[OA\Tag(name: 'Playlist Export', description: 'Playlist export endpoints')]
class PlaylistExportController extends AbstractController
{
    public function __construct(
        private PlaylistExportService $playlistExportService,
        private ProviderManager $providerManager,
        private PlaylistRepository $playlistRepository,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/{id}/export/{platform}', name: 'api_playlist_export', methods: ['POST'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
    #[OA\Post(
        path: '/api/playlist/{id}/export/{platform}',
        summary: 'Export playlist to streaming platform',
        description: 'Exports a playlist to the specified streaming platform (Spotify, YouTube, SoundCloud)',
        tags: ['Playlist Export'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Playlist ID',
                schema: new OA\Schema(type: 'string', example: '01234567-89ab-cdef-0123-456789abcdef')
            ),
            new OA\Parameter(
                name: 'platform',
                in: 'path',
                required: true,
                description: 'Platform to export to',
                schema: new OA\Schema(type: 'string', enum: ['spotify', 'google', 'soundcloud'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Playlist exported successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'playlist_id', type: 'string', description: 'Platform playlist ID'),
                        new OA\Property(property: 'playlist_url', type: 'string', description: 'URL to the exported playlist'),
                        new OA\Property(property: 'exported_tracks', type: 'integer', description: 'Number of successfully exported tracks'),
                        new OA\Property(property: 'failed_tracks', type: 'integer', description: 'Number of tracks that failed to export'),
                        new OA\Property(property: 'platform', type: 'string', description: 'Platform name'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request or platform not supported',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Platform not supported'),
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
                response: 403,
                description: 'User not connected to platform, cannot export, or free plan limit reached',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'User is not connected to spotify'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Playlist not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Playlist not found'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Export failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Failed to export playlist'),
                    ]
                )
            ),
        ]
    )]
    public function exportPlaylist(Playlist $playlist, string $platform, Request $request): JsonResponse
    {
        /** @var string $accessToken */
        $accessToken = $request->cookies->get('AUTH_TOKEN');
        /** @var User $user */
        $user = $this->providerManager->findByAccessToken($accessToken);

        $validationResponse = $this->validateExportRequest($playlist, $user);
        if (null !== $validationResponse) {
            return $validationResponse;
        }

        try {
            $exportResult = $this->performExport($playlist, $user, $platform);

            return new JsonResponse([
                'success' => true,
                ...$exportResult->toArray(),
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->handleInvalidArgumentException($e, $playlist, $platform);
        } catch (RuntimeException $e) {
            return $this->handleRuntimeException($e, $playlist, $platform);
        } catch (Exception $e) {
            return $this->handleUnexpectedException($e, $playlist, $platform);
        }
    }

    private function validateExportRequest(Playlist $playlist, User $user): ?JsonResponse
    {
        if ($playlist->getUser() !== $user) {
            return new JsonResponse(['error' => 'playlist.export.not_owner'], Response::HTTP_FORBIDDEN);
        }

        $subscription = $user->getSubscription();
        if (null === $subscription || !$subscription->isActive()) {
            return new JsonResponse(['error' => 'playlist.export.subscription_required'], Response::HTTP_FORBIDDEN);
        }

        $isFreePlan = Plan::FREE === $subscription->getPlan()?->getName();
        if ($isFreePlan && $playlist->hasBeenExported()) {
            return new JsonResponse(['error' => 'playlist.export.free_user_limit_reached'], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    private function performExport(Playlist $playlist, User $user, string $platform): ExportResult
    {
        $exportResult = $this->playlistExportService->exportPlaylist($playlist, $user, $platform);

        $playlist->setExportedPlaylistId($exportResult->playlistId);
        $playlist->setExportedPlaylistUrl($exportResult->playlistUrl);

        $subscription = $user->getSubscription();
        $isFreePlan = Plan::FREE === $subscription?->getPlan()?->getName();
        if ($isFreePlan) {
            $playlist->setHasBeenExported(true);
        }

        $this->playlistRepository->save($playlist, true);

        return $exportResult;
    }

    private function handleInvalidArgumentException(InvalidArgumentException $e, Playlist $playlist, string $platform): JsonResponse
    {
        $this->logger->warning('Playlist export failed - invalid argument', [
            'playlistId' => $playlist->getId(),
            'platform' => $platform,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    }

    private function handleRuntimeException(RuntimeException $e, Playlist $playlist, string $platform): JsonResponse
    {
        $this->logger->error('Playlist export failed', [
            'playlistId' => $playlist->getId(),
            'platform' => $platform,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return new JsonResponse(['error' => 'playlist.export.failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function handleUnexpectedException(Exception $e, Playlist $playlist, string $platform): JsonResponse
    {
        $this->logger->error('Unexpected error during playlist export', [
            'playlistId' => $playlist->getId(),
            'platform' => $platform,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return new JsonResponse(['error' => 'playlist.export.unexpected_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
