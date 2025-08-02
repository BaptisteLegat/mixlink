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
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyExportService implements ExportServiceInterface
{
    private const string API_BASE_URL = 'https://api.spotify.com/v1';
    private const string CREATE_PLAYLIST_URL = '/users/{user_id}/playlists';
    private const string ADD_TRACKS_URL = '/playlists/{playlist_id}/tracks';
    private const int MAX_TRACKS_PER_REQUEST = 100;

    public function __construct(
        private HttpClientInterface $httpClient,
        private OAuthTokenManager $tokenManager,
    ) {
    }

    #[Override]
    public function exportPlaylist(Playlist $playlist, User $user): ExportResult
    {
        $provider = $user->getProviderByName('spotify');
        if (null === $provider) {
            throw new InvalidArgumentException('User is not connected to Spotify');
        }

        $userProfile = $this->getSpotifyUserProfile($provider);
        $spotifyUserId = $userProfile['id'];

        $playlistData = $this->createSpotifyPlaylist($provider, $spotifyUserId, $playlist->getName() ?? 'MixLink Playlist');

        $playlistId = $playlistData['id'];
        $playlistUrl = $playlistData['external_urls']['spotify'];

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
        return 'spotify';
    }

    #[Override]
    public function isUserConnected(User $user): bool
    {
        $provider = $user->getProviderByName('spotify');

        return null !== $provider && null !== $provider->getAccessToken();
    }

    /**
     * @return array{id: string, display_name: string}
     */
    private function getSpotifyUserProfile(Provider $provider): array
    {
        $data = $this->makeAuthenticatedRequest(
            $provider,
            'GET',
            self::API_BASE_URL.'/me'
        );

        /** @var array{id: string, display_name: string} */
        return $data;
    }

    /**
     * @return array{id: string, external_urls: array{spotify: string}}
     */
    private function createSpotifyPlaylist(Provider $provider, string $spotifyUserId, string $playlistName): array
    {
        $url = str_replace('{user_id}', $spotifyUserId, self::API_BASE_URL.self::CREATE_PLAYLIST_URL);

        $data = $this->makeAuthenticatedRequest(
            $provider,
            'POST',
            $url,
            [
                'json' => [
                    'name' => $playlistName,
                    'description' => 'Created with MixLink',
                    'public' => false,
                ],
            ]
        );

        /** @var array{id: string, external_urls: array{spotify: string}} */
        return $data;
    }

    /**
     * @param Collection<int, Song> $songs
     *
     * @return array{exported_tracks: int, failed_tracks: int}
     */
    private function addTracksToPlaylist(Provider $provider, string $playlistId, Collection $songs): array
    {
        $url = str_replace('{playlist_id}', $playlistId, self::API_BASE_URL.self::ADD_TRACKS_URL);

        $exportedTracks = 0;
        $failedTracks = 0;

        /** @var array<string> $trackUris */
        $trackUris = [];

        foreach ($songs as $song) {
            $spotifyId = $song->getSpotifyId();
            if (null === $spotifyId) {
                ++$failedTracks;
                continue;
            }

            $trackUris[] = 'spotify:track:'.$spotifyId;
            ++$exportedTracks;

            if (count($trackUris) >= self::MAX_TRACKS_PER_REQUEST) {
                $this->addTrackBatch($provider, $url, $trackUris);
                $trackUris = [];
            }
        }

        if (!empty($trackUris)) {
            $this->addTrackBatch($provider, $url, $trackUris);
        }

        return [
            'exported_tracks' => $exportedTracks,
            'failed_tracks' => $failedTracks,
        ];
    }

    /**
     * @param array<string> $trackUris
     */
    private function addTrackBatch(Provider $provider, string $url, array $trackUris): void
    {
        $this->makeAuthenticatedRequest(
            $provider,
            'POST',
            $url,
            [
                'json' => [
                    'uris' => $trackUris,
                ],
            ]
        );
    }

    /**
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

            throw new RuntimeException('API request failed with status: '.$response->getStatusCode());
        } catch (RuntimeException $e) {
            // If it's a 401 error, try to refresh the token and retry once
            if (str_contains($e->getMessage(), '401')) {
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
