<?php

namespace App\Controller;

use App\Playlist\Mapper\PlaylistMapper;
use App\Playlist\PlaylistManager;
use App\Song\SongMapper;
use App\Voter\AuthenticationVoter;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/playlist', name: 'api_playlist_')]
class PlaylistController extends AbstractController
{
    public function __construct(
        private PlaylistManager $playlistManager,
        private PlaylistMapper $playlistMapper,
        private SongMapper $songMapper,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/{id}/add-song', name: 'add_song', methods: ['POST'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
    #[OA\Post(
        path: '/api/playlist/{id}/add-song',
        summary: 'Add a song to a playlist',
        description: 'Adds a song to the playlist. Checks the subscription plan for song limit.',
        tags: ['Playlist'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Playlist ID',
                schema: new OA\Schema(type: 'string', example: '01234567-89ab-cdef-0123-456789abcdef')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['spotifyId', 'title', 'artists', 'image', 'externalUrl'],
                properties: [
                    new OA\Property(property: 'spotifyId', type: 'string', description: 'Spotify ID', example: '6rqhFgbbKwnb9MLmUQDhG6'),
                    new OA\Property(property: 'title', type: 'string', description: 'Song title', example: 'One More Time'),
                    new OA\Property(property: 'artists', type: 'string', description: 'Artists (comma separated)', example: 'Daft Punk'),
                    new OA\Property(property: 'image', type: 'string', description: 'Image URL', example: 'https://i.scdn.co/image/ab67616d0000b273...'),
                    new OA\Property(property: 'externalUrl', type: 'string', description: 'Spotify URL', example: 'https://open.spotify.com/track/6rqhFgbbKwnb9MLmUQDhG6'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Song added successfully'
            ),
            new OA\Response(
                response: 400,
                description: 'Limit reached or invalid data',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string')])
            ),
            new OA\Response(
                response: 404,
                description: 'Playlist not found'
            ),
        ]
    )]
    public function addSong(string $id, Request $request): JsonResponse
    {
        try {
            /** @var array<string, string|null> $data */
            $data = json_decode($request->getContent(), true);
            if (!is_array($data)) {
                return new JsonResponse(['error' => 'playlist.add_song.invalid_data'], Response::HTTP_BAD_REQUEST);
            }
            $playlist = $this->playlistManager->findPlaylistById($id);
            if (!$playlist) {
                return new JsonResponse(['error' => 'playlist.add_song.not_found'], Response::HTTP_NOT_FOUND);
            }
            $user = $playlist->getUser();
            $plan = $user?->getSubscription()?->getPlan()?->getName() ?? 'free';
            $maxSongs = 'free' === $plan ? 30 : null;
            if (null !== $maxSongs && $playlist->getSongs()->count() >= $maxSongs) {
                return new JsonResponse(['error' => 'playlist.add_song.limit_reached'], Response::HTTP_BAD_REQUEST);
            }

            $song = $this->playlistManager->addSongToPlaylist($playlist, $data);
            $songModel = $this->songMapper->mapModel($song);

            return new JsonResponse(['success' => true, 'song' => $songModel->toArray()]);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            $this->logger->error('Error adding song to playlist', [
                'playlistId' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'playlist.add_song.error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/playlist/{id}',
        summary: 'Get a playlist by ID',
        description: 'Returns the playlist with all its songs',
        tags: ['Playlist'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Playlist ID',
                schema: new OA\Schema(type: 'string', example: '01234567-89ab-cdef-0123-456789abcdef')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Playlist found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'songs', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Playlist not found'
            ),
        ]
    )]
    public function getPlaylist(string $id): JsonResponse
    {
        $playlist = $this->playlistManager->findPlaylistById($id);
        if (!$playlist) {
            return new JsonResponse(['error' => 'playlist.get.not_found'], Response::HTTP_NOT_FOUND);
        }
        $playlistModel = $this->playlistMapper->mapModel($playlist);

        return new JsonResponse($playlistModel->toArray());
    }
}
