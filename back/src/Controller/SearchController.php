<?php

namespace App\Controller;

use App\Service\SpotifyService;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    public function __construct(private SpotifyService $spotifyService, private LoggerInterface $logger)
    {
    }

    #[Route('/api/search/music', name: 'api_search_music', methods: ['GET'])]
    #[OA\Get(
        path: '/api/search/music',
        summary: 'Search for music on Spotify',
        description: 'Searches for tracks using the Spotify public API (no authentication required)',
        tags: ['Search', 'Spotify'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: true,
                description: 'Search text (title, artist, etc.)',
                schema: new OA\Schema(type: 'string', example: 'Daft Punk')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'string'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'artists', type: 'array', items: new OA\Items(type: 'string')),
                            new OA\Property(property: 'image', type: 'string', nullable: true),
                            new OA\Property(property: 'preview_url', type: 'string', nullable: true),
                            new OA\Property(property: 'external_url', type: 'string', nullable: true),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Missing search parameter',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string')])
            ),
            new OA\Response(
                response: 500,
                description: 'Internal error',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string')])
            ),
        ]
    )]
    public function searchMusic(Request $request): JsonResponse
    {
        /** @var string|null $query */
        $query = $request->query->get('q');
        if (null === $query || '' === trim($query)) {
            return new JsonResponse(['error' => 'search.error.missing_query'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $results = $this->spotifyService->searchTracks($query);
            $arrayResults = array_map(fn ($track) => $track->toArray(), $results);

            return new JsonResponse($arrayResults);
        } catch (Exception $e) {
            $this->logger->error('Spotify search error', [
                'query' => $query,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => 'search.error.unknown'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
