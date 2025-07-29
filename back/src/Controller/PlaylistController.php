<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Playlist\Mapper\PlaylistMapper;
use App\Playlist\PlaylistManager;
use App\Session\Publisher\SessionMercurePublisher;
use App\Song\SongMapper;
use App\Song\SongModel;
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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/playlist', name: 'api_playlist_')]
class PlaylistController extends AbstractController
{
    public function __construct(
        private PlaylistManager $playlistManager,
        private PlaylistMapper $playlistMapper,
        private SongMapper $songMapper,
        private LoggerInterface $logger,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private SessionMercurePublisher $sessionMercurePublisher,
    ) {
    }

    #[Route('/{id}/add-song', name: 'add_song', methods: ['POST'])]
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
                required: ['spotifyId', 'title', 'artists', 'image'],
                properties: [
                    new OA\Property(property: 'spotifyId', type: 'string', description: 'Spotify ID', example: '6rqhFgbbKwnb9MLmUQDhG6'),
                    new OA\Property(property: 'title', type: 'string', description: 'Song title', example: 'One More Time'),
                    new OA\Property(property: 'artists', type: 'string', description: 'Artists (comma separated)', example: 'Daft Punk'),
                    new OA\Property(property: 'image', type: 'string', description: 'Image URL', example: 'https://i.scdn.co/image/ab67616d0000b273...'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Song added successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'song', type: 'object', properties: [
                            new OA\Property(property: 'spotifyId', type: 'string'),
                            new OA\Property(property: 'title', type: 'string'),
                            new OA\Property(property: 'artists', type: 'string'),
                            new OA\Property(property: 'image', type: 'string'),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                        ]),
                    ]
                )
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
    public function addSong(Playlist $playlist, Request $request): JsonResponse
    {
        try {
            /** @var SongModel $songModel */
            $songModel = $this->serializer->deserialize(
                $request->getContent(),
                SongModel::class,
                'json'
            );

            $errors = $this->validator->validate($songModel);
            if (count($errors) > 0) {
                $errorsArray = [];
                foreach ($errors as $error) {
                    $errorsArray[] = [
                        'propertyPath' => $error->getPropertyPath(),
                        'message' => $error->getMessage(),
                    ];
                }

                return new JsonResponse([
                    'errors' => $errorsArray,
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = $playlist->getUser();
            $plan = $user?->getSubscription()?->getPlan();
            if (null === $plan) {
                return new JsonResponse(['error' => 'playlist.add_song.no_subscription'], Response::HTTP_BAD_REQUEST);
            }

            if (null !== $plan->getMaxSongs() && $playlist->getSongs()->count() >= $plan->getMaxSongs()) {
                return new JsonResponse(['error' => 'playlist.add_song.limit_reached'], Response::HTTP_BAD_REQUEST);
            }

            $song = $this->playlistManager->addSongToPlaylist($playlist, $songModel);
            $songModel = $this->songMapper->mapModel($song);

            $this->sessionMercurePublisher->publishPlaylistUpdate(
                (string) $playlist->getSessionCode(),
                $this->playlistMapper->mapModel($playlist)->toArray()
            );

            return new JsonResponse(['success' => true, 'song' => $songModel->toArray()]);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            $this->logger->error('Error adding song to playlist', [
                'playlistId' => (string) $playlist->getId(),
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
                        new OA\Property(property: 'songs', type: 'array', items: new OA\Items(type: 'object', properties: [
                            new OA\Property(property: 'spotifyId', type: 'string'),
                            new OA\Property(property: 'title', type: 'string'),
                            new OA\Property(property: 'artists', type: 'string'),
                            new OA\Property(property: 'image', type: 'string'),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                        ])),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Playlist not found'
            ),
        ]
    )]
    public function getPlaylist(Playlist $playlist): JsonResponse
    {
        $playlistModel = $this->playlistMapper->mapModel($playlist);

        return new JsonResponse($playlistModel->toArray());
    }

    #[Route('/{id}/remove-song/{spotifyId}', name: 'remove_song', methods: ['DELETE'])]
    #[IsGranted(AuthenticationVoter::IS_AUTHENTICATED, message: 'common.unauthorized')]
    #[OA\Delete(
        path: '/api/playlist/{id}/remove-song/{spotifyId}',
        summary: 'Remove a song from a playlist',
        description: 'Removes a song from the playlist (host only)',
        tags: ['Playlist'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Playlist ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'spotifyId', in: 'path', required: true, description: 'Spotify ID of the song', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Song removed, playlist updated'),
            new OA\Response(response: 404, description: 'Playlist or song not found'),
            new OA\Response(response: 400, description: 'Error removing song from playlist'),
        ]
    )]
    public function removeSong(Playlist $playlist, string $spotifyId): JsonResponse
    {
        try {
            $this->playlistManager->removeSongFromPlaylist($playlist, $spotifyId);

            $this->sessionMercurePublisher->publishPlaylistUpdate(
                (string) $playlist->getSessionCode(),
                $this->playlistMapper->mapModel($playlist)->toArray()
            );

            return new JsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->logger->error('Error removing song from playlist', [
                'playlistId' => (string) $playlist->getId(),
                'spotifyId' => $spotifyId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'playlist.remove_song.error'], Response::HTTP_BAD_REQUEST);
        }
    }
}
