<?php

namespace App\Service\Export;

use App\Entity\Playlist;
use App\Entity\Provider;
use App\Entity\Song;
use App\Entity\User;
use App\Service\Export\Model\ExportResult;
use App\Service\OAuthTokenManager;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleExportService implements ExportServiceInterface
{
    private const string YOUTUBE_API_BASE_URL = 'https://www.googleapis.com/youtube/v3';
    private const string CREATE_PLAYLIST_URL = '/playlists';
    private const string ADD_PLAYLIST_ITEM_URL = '/playlistItems';

    public function __construct(
        private HttpClientInterface $httpClient,
        private OAuthTokenManager $tokenManager,
        private LoggerInterface $logger,
    ) {
    }

    #[Override]
    public function exportPlaylist(Playlist $playlist, User $user): ExportResult
    {
        $provider = $user->getProviderByName('google');
        if (null === $provider) {
            throw new InvalidArgumentException('User is not connected to Google');
        }

        $accessToken = $this->tokenManager->getValidAccessToken($provider);

        $playlistData = $this->createYouTubePlaylist($provider, $playlist->getName() ?? 'MixLink Playlist');

        $playlistId = $playlistData['id'];
        $playlistUrl = 'https://www.youtube.com/playlist?list='.$playlistId;

        $exportResult = $this->addTracksToPlaylist($provider, $playlistId, $playlist->getSongs());

        return new ExportResult(
            playlistId: $playlistId,
            playlistUrl: $playlistUrl,
            exportedTracks: $exportResult['exported_tracks'],
            failedTracks: $exportResult['failed_tracks'],
            platform: $this->getPlatformName(),
        );
    }

    #[Override]
    public function getPlatformName(): string
    {
        return 'google';
    }

    #[Override]
    public function isUserConnected(User $user): bool
    {
        $provider = $user->getProviderByName('google');

        return null !== $provider && null !== $provider->getAccessToken();
    }

    /**
     * @return array{id: string}
     */
    private function createYouTubePlaylist(Provider $provider, string $playlistName): array
    {
        $data = $this->makeAuthenticatedRequest(
            $provider,
            'POST',
            self::YOUTUBE_API_BASE_URL.self::CREATE_PLAYLIST_URL,
            [
                'query' => [
                    'part' => 'snippet,status',
                ],
                'json' => [
                    'snippet' => [
                        'title' => $playlistName,
                        'description' => 'Created with MixLink',
                    ],
                    'status' => [
                        'privacyStatus' => 'private',
                    ],
                ],
            ]
        );

        /** @var array{id: string} */
        return $data;
    }

    /**
     * @param Collection<int, Song> $songs
     *
     * @return array{exported_tracks: int, failed_tracks: int}
     */
    private function addTracksToPlaylist(Provider $provider, string $playlistId, Collection $songs): array
    {
        $exportedTracks = 0;
        $failedTracks = 0;

        foreach ($songs as $song) {
            try {
                $title = $song->getTitle();
                $artists = $song->getArtists();

                if (null === $title || null === $artists) {
                    ++$failedTracks;
                    continue;
                }

                $videoId = $this->searchYouTubeVideo($provider, $title, $artists);

                if (null === $videoId) {
                    ++$failedTracks;
                    continue;
                }

                try {
                    $this->addVideoToPlaylist($provider, $playlistId, $videoId);
                    ++$exportedTracks;

                    // To avoid hitting YouTube API rate limits, we add a delay after each successful track addition
                    if ($exportedTracks < $songs->count()) {
                        usleep(500000); // 0.5 seconds delay
                    }
                } catch (RuntimeException $e) {
                    $this->logger->error("YouTube: Failed to add video $videoId for '$title' by '$artists': ".$e->getMessage());
                    ++$failedTracks;
                }
            } catch (RuntimeException $e) {
                ++$failedTracks;
            }
        }

        return [
            'exported_tracks' => $exportedTracks,
            'failed_tracks' => $failedTracks,
        ];
    }

    private function searchYouTubeVideo(Provider $provider, string $title, string $artists): ?string
    {
        $searchQuery = $title.' '.$artists;

        $data = $this->makeAuthenticatedRequest(
            $provider,
            'GET',
            self::YOUTUBE_API_BASE_URL.'/search',
            [
                'query' => [
                    'part' => 'id',
                    'q' => $searchQuery,
                    'type' => 'video',
                    'maxResults' => 1,
                    'videoCategoryId' => '10', // Music category
                ],
            ]
        );

        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            return null;
        }

        /** @var array{id?: array{videoId?: string}} */
        $firstItem = $data['items'][0];

        return $firstItem['id']['videoId'] ?? null;
    }

    private function addVideoToPlaylist(Provider $provider, string $playlistId, string $videoId): void
    {
        $maxRetries = 3;
        $retryDelay = 1; // 1 second between retries

        for ($attempt = 1; $attempt <= $maxRetries; ++$attempt) {
            try {
                $this->makeAuthenticatedRequest(
                    $provider,
                    'POST',
                    self::YOUTUBE_API_BASE_URL.self::ADD_PLAYLIST_ITEM_URL,
                    [
                        'query' => [
                            'part' => 'snippet',
                        ],
                        'json' => [
                            'snippet' => [
                                'playlistId' => $playlistId,
                                'resourceId' => [
                                    'kind' => 'youtube#video',
                                    'videoId' => $videoId,
                                ],
                            ],
                        ],
                    ]
                );

                return;
            } catch (RuntimeException $e) {
                // Log the original error message from YouTube API
                $this->logger->error("YouTube API Error (attempt $attempt/$maxRetries): ".$e->getMessage());

                // If this is the last attempt, rethrow the exception
                if ($attempt === $maxRetries) {
                    // Handle specific YouTube API errors
                    if (str_contains($e->getMessage(), '409')) {
                        throw new RuntimeException('YouTube API conflict (409): '.$e->getMessage());
                    }

                    if (str_contains($e->getMessage(), '403')) {
                        throw new RuntimeException('YouTube API access denied (403): '.$e->getMessage());
                    }

                    throw $e;
                }

                sleep($retryDelay);

                // Increase the delay for the next retry (exponential backoff)
                $retryDelay *= 2;
            }
        }
    }

    /**
     * Make an authenticated request with automatic token refresh on 401 errors.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function makeAuthenticatedRequest(Provider $provider, string $method, string $url, array $options = []): array
    {
        $accessToken = $this->tokenManager->getValidAccessToken($provider);

        /** @var array<string, string> $headers */
        $headers = $options['headers'] ?? [];
        $options['headers'] = array_merge($headers, [
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ]);

        try {
            $response = $this->httpClient->request($method, $url, $options);

            if (Response::HTTP_OK === $response->getStatusCode() || Response::HTTP_CREATED === $response->getStatusCode()) {
                /** @var array<string, mixed> */
                return $response->toArray(false);
            }

            // Get the actual error message from YouTube API
            $errorContent = $response->toArray(false);
            $errorMessage = 'Unknown error';

            if (isset($errorContent['error']['message']) && is_string($errorContent['error']['message'])) {
                $errorMessage = $errorContent['error']['message'];
            } elseif (isset($errorContent['error']['errors']) && is_array($errorContent['error']['errors'])) {
                $encodedErrors = json_encode($errorContent['error']['errors']);
                $errorMessage = is_string($encodedErrors) ? $encodedErrors : 'Unknown error';
            }

            if (Response::HTTP_FORBIDDEN === $response->getStatusCode()) {
                throw new RuntimeException('YouTube API access denied (403): '.$errorMessage);
            }

            if (Response::HTTP_UNAUTHORIZED === $response->getStatusCode()) {
                throw new RuntimeException('YouTube API authentication failed (401): '.$errorMessage);
            }

            if (Response::HTTP_CONFLICT === $response->getStatusCode()) {
                throw new RuntimeException('YouTube API conflict (409): '.$errorMessage);
            }

            throw new RuntimeException('YouTube API request failed ('.$response->getStatusCode().'): '.$errorMessage);
        } catch (RuntimeException $e) {
            // If it's a 401 error, try to refresh the token and retry once
            if (str_contains($e->getMessage(), '401')) {
                // Check if we have a refresh token available
                if (!$this->tokenManager->hasRefreshToken($provider)) {
                    throw new RuntimeException('Token expired and no refresh token available. Please reconnect to Google to get a new token.');
                }

                try {
                    $newAccessToken = $this->tokenManager->refreshAccessToken($provider);

                    $options['headers']['Authorization'] = 'Bearer '.$newAccessToken;
                    $response = $this->httpClient->request($method, $url, $options);

                    if (Response::HTTP_OK === $response->getStatusCode() || Response::HTTP_CREATED === $response->getStatusCode()) {
                        /** @var array<string, mixed> */
                        return $response->toArray(false);
                    }
                } catch (RuntimeException $refreshException) {
                    throw new RuntimeException('Failed to refresh token and retry request: '.$refreshException->getMessage());
                }
            }

            throw $e;
        }
    }
}
