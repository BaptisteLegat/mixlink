<?php

namespace App\Service\Export;

use App\ApiResource\ApiReference;
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
use Symfony\Contracts\HttpClient\ResponseInterface;

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

        $this->tokenManager->getValidAccessToken($provider);

        $playlistData = $this->createYouTubePlaylist($provider, $playlist->getName() ?? 'mixlink Playlist');

        $playlistId = $playlistData['id'];
        $playlistUrl = 'https://www.youtube.com/playlist?list='.$playlistId;

        $exportResult = $this->addTracksToPlaylist($provider, $playlistId, $playlist->getSongs());

        return new ExportResult(
            playlistId: $playlistId,
            playlistUrl: $playlistUrl,
            exportedTracks: $exportResult['exported_tracks'],
            failedTracks: $exportResult['failed_tracks'],
            platform: ApiReference::GOOGLE,
        );
    }

    #[Override]
    public function isUserConnected(User $user): bool
    {
        $provider = $user->getProviderByName(ApiReference::GOOGLE);

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
                        'description' => 'Created with mixlink',
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
                    $this->addVideoToPlaylist($provider, $playlistId, $videoId) ? ++$exportedTracks : ++$failedTracks;

                    // To avoid hitting YouTube API rate limits, we add a delay after each track addition
                    if (($exportedTracks + $failedTracks) < $songs->count()) {
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

    private function addVideoToPlaylist(Provider $provider, string $playlistId, string $videoId): bool
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

                return true;
            } catch (RuntimeException $e) {
                // Log the original error message from YouTube API
                $this->logger->error("YouTube API Error (attempt $attempt/$maxRetries): ".$e->getMessage());

                // Handle specific YouTube API errors that shouldn't cause a complete failure
                if (str_contains($e->getMessage(), '409')) {
                    // 409 conflict often means the video is already in the playlist or duplicate operation
                    // This is not a critical error, so we can consider it successful
                    $this->logger->warning('YouTube API conflict (409) - video possibly already in playlist or duplicate operation: '.$e->getMessage());

                    return true; // Consider as successful since the video is in the playlist
                }

                // If this is the last attempt, rethrow the exception for other errors
                if ($attempt === $maxRetries) {
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

        // This should never be reached, but just in case
        return false;
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
        $options = $this->prepareGoogleRequestOptions($options, $accessToken);

        try {
            $response = $this->httpClient->request($method, $url, $options);

            if ($this->isGoogleSuccessfulResponse($response)) {
                /** @var array<string, mixed> */
                return $response->toArray(false);
            }

            $this->throwGoogleApiException($response);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), '401')) {
                return $this->retryGoogleWithRefreshedToken($provider, $method, $url, $options);
            }

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function prepareGoogleRequestOptions(array $options, string $accessToken): array
    {
        /** @var array<string, string> $headers */
        $headers = $options['headers'] ?? [];
        $options['headers'] = array_merge($headers, [
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ]);

        return $options;
    }

    private function isGoogleSuccessfulResponse(ResponseInterface $response): bool
    {
        return Response::HTTP_OK === $response->getStatusCode() || Response::HTTP_CREATED === $response->getStatusCode();
    }

    private function throwGoogleApiException(ResponseInterface $response): never
    {
        $errorContent = $response->toArray(false);
        /** @var array<string, mixed> $errorContent */
        $errorMessage = $this->extractGoogleErrorMessage($errorContent);

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
    }

    /**
     * @param array<string, mixed> $errorContent
     */
    private function extractGoogleErrorMessage(array $errorContent): string
    {
        if (isset($errorContent['error']['message']) && is_string($errorContent['error']['message'])) {
            return $errorContent['error']['message'];
        }

        if (isset($errorContent['error']['errors']) && is_array($errorContent['error']['errors'])) {
            $encodedErrors = json_encode($errorContent['error']['errors']);

            return is_string($encodedErrors) ? $encodedErrors : 'Unknown error';
        }

        return 'Unknown error';
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function retryGoogleWithRefreshedToken(Provider $provider, string $method, string $url, array $options): array
    {
        if (!$this->tokenManager->hasRefreshToken($provider)) {
            throw new RuntimeException('Token expired and no refresh token available. Please reconnect to Google to get a new token.');
        }

        try {
            $newAccessToken = $this->tokenManager->refreshAccessToken($provider);
            /** @var array<string, string> $headers */
            $headers = $options['headers'] ?? [];
            $headers['Authorization'] = 'Bearer '.$newAccessToken;
            $options['headers'] = $headers;

            $response = $this->httpClient->request($method, $url, $options);

            if ($this->isGoogleSuccessfulResponse($response)) {
                /** @var array<string, mixed> */
                return $response->toArray(false);
            }

            $this->throwGoogleApiException($response);
        } catch (RuntimeException $refreshException) {
            throw new RuntimeException('Failed to refresh token and retry request: '.$refreshException->getMessage());
        }
    }
}
