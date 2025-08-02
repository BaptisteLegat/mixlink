<?php

namespace App\Service\Export;

use App\Entity\Playlist;
use App\Entity\Provider;
use App\Entity\Song;
use App\Entity\User;
use App\Service\OAuthTokenManager;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SoundCloudExportService implements ExportServiceInterface
{
    private const string API_BASE_URL = 'https://api.soundcloud.com';
    private const string CREATE_PLAYLIST_URL = '/playlists';
    private const int MIN_SCORE_THRESHOLD = 15;
    private const int MIN_PARTIAL_LENGTH = 4;
    private const int REMIX_SCORE_DIVISOR = 3; // Instead of multiplier 0.3, we divide by 3

    public function __construct(
        private HttpClientInterface $httpClient,
        private OAuthTokenManager $tokenManager,
        private LoggerInterface $logger,
    ) {
    }

    #[Override]
    public function exportPlaylist(Playlist $playlist, User $user): array
    {
        $provider = $user->getProviderByName('soundcloud');
        if (null === $provider) {
            throw new InvalidArgumentException('User is not connected to SoundCloud');
        }

        $playlistData = $this->createSoundCloudPlaylist($provider, $playlist->getName() ?? 'MixLink Playlist');

        $playlistId = $playlistData['id'];
        $playlistUrl = $playlistData['permalink_url'];

        $exportResult = $this->addTracksToPlaylist($provider, $playlistId, $playlist->getSongs());

        return [
            'playlist_id' => (string) $playlistId,
            'playlist_url' => $playlistUrl,
            'exported_tracks' => $exportResult['exported_tracks'],
            'failed_tracks' => $exportResult['failed_tracks'],
        ];
    }

    #[Override]
    public function getPlatformName(): string
    {
        return 'soundcloud';
    }

    #[Override]
    public function isUserConnected(User $user): bool
    {
        $provider = $user->getProviderByName('soundcloud');

        return null !== $provider && null !== $provider->getAccessToken();
    }

    /**
     * @return array{id: int, permalink_url: string}
     */
    private function createSoundCloudPlaylist(Provider $provider, string $playlistName): array
    {
        $data = $this->makeAuthenticatedRequest(
            $provider,
            'POST',
            self::API_BASE_URL.self::CREATE_PLAYLIST_URL,
            [
                'json' => [
                    'playlist' => [
                        'title' => $playlistName,
                        'description' => 'Created with MixLink',
                        'sharing' => 'private',
                    ],
                ],
            ]
        );

        /** @var array{id: int, permalink_url: string} */
        return $data;
    }

    /**
     * @param Collection<int, Song> $songs
     *
     * @return array{exported_tracks: int, failed_tracks: int}
     */
    private function addTracksToPlaylist(Provider $provider, int $playlistId, Collection $songs): array
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

                $trackId = $this->searchSoundCloudTrack($provider, $title, $artists);

                if (null === $trackId) {
                    $this->logger->warning("SoundCloud: No track found for '$title' by '$artists'");
                    ++$failedTracks;
                    continue;
                }

                $this->logger->info("SoundCloud: Found track ID $trackId for '$title' by '$artists'");
                $this->addTrackToPlaylist($provider, $playlistId, $trackId);
                ++$exportedTracks;
            } catch (RuntimeException $e) {
                $this->logger->error('SoundCloud: Error adding track to playlist - '.$e->getMessage());
                ++$failedTracks;
            }
        }

        return [
            'exported_tracks' => $exportedTracks,
            'failed_tracks' => $failedTracks,
        ];
    }

    private function searchSoundCloudTrack(Provider $provider, string $title, string $artists): ?int
    {
        $cleanTitle = $this->cleanSearchTerm($title);
        $cleanArtists = $this->cleanSearchTerm($artists);

        // Essayer plusieurs combinaisons de recherche
        $searchQueries = [
            $cleanTitle.' '.$cleanArtists,
            $cleanTitle,
            $cleanArtists.' '.$cleanTitle,
            $cleanTitle.' '.$this->extractMainArtist($cleanArtists),
        ];

        foreach ($searchQueries as $searchQuery) {
            try {
                /** @var array<int, array<string, mixed>> $data */
                $data = $this->makeAuthenticatedRequest(
                    $provider,
                    'GET',
                    self::API_BASE_URL.'/tracks',
                    [
                        'query' => [
                            'q' => $searchQuery,
                            'limit' => 15,
                            'filter' => 'public',
                            'order' => 'hotness',
                        ],
                    ]
                );

                if (!empty($data)) {
                    $bestMatch = $this->findBestMatch($data, $cleanTitle, $cleanArtists);
                    if (null !== $bestMatch) {
                        return $bestMatch;
                    }
                }
            } catch (RuntimeException $e) {
                // Continue avec la prochaine requête
                continue;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $tracks
     */
    private function findBestMatch(array $tracks, string $title, string $artists): ?int
    {
        $titleLower = strtolower($title);
        $artistsLower = strtolower($artists);

        $bestScore = 0;
        $bestTrackId = null;

        foreach ($tracks as $track) {
            if (!isset($track['id']) || !is_int($track['id'])) {
                continue;
            }

            $trackTitle = strtolower((string) ($track['title'] ?? ''));
            $trackUser = strtolower((string) ($track['user']['username'] ?? ''));

            $isRemix = $this->isRemixOrCover($trackTitle);

            $score = $this->calculateMatchScore($trackTitle, $trackUser, $titleLower, $artistsLower);

            if ($isRemix) {
                $score = (int) ($score / self::REMIX_SCORE_DIVISOR);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTrackId = $track['id'];
            }
        }

        if ($bestScore >= self::MIN_SCORE_THRESHOLD) {
            return $bestTrackId;
        }

        return null;
    }

    private function isRemixOrCover(string $trackTitle): bool
    {
        // Check for remix keywords that are NOT in parentheses/brackets
        $generalRemixKeywords = [
            'mashup', 'cover', 'vs', 'version', 'rework', 'flip', 'dub',
            'instrumental', 'karaoke', 'acoustic', 'live', 'extended', 'radio edit', 'club mix',
        ];

        foreach ($generalRemixKeywords as $keyword) {
            if (str_contains($trackTitle, $keyword)) {
                return true;
            }
        }

        // Check for specific remix keywords ONLY in parentheses
        if (preg_match('/\([^)]*(remix|edit|mix|vip|bootleg)[^)]*\)/i', $trackTitle)) {
            return true;
        }

        // Check for specific remix keywords ONLY in brackets
        if (preg_match('/\[[^\]]*(remix|edit|mix|vip|bootleg)[^\]]*\]/i', $trackTitle)) {
            return true;
        }

        return false;
    }

    private function calculateMatchScore(string $trackTitle, string $trackUser, string $searchTitle, string $searchArtists): int
    {
        $score = 0;

        if ($trackTitle === $searchTitle) {
            $score += 100;
        }

        if ($trackUser === $searchArtists) {
            $score += 50;
        }

        if (str_contains($trackTitle, $searchTitle)) {
            $score += 40;
        }

        if (str_contains($trackUser, $searchArtists)) {
            $score += 30;
        }

        if (str_contains($trackTitle, $searchTitle) && str_contains($trackUser, $searchArtists)) {
            $score += 20;
        }

        if (strlen($searchTitle) >= self::MIN_PARTIAL_LENGTH && str_contains($trackTitle, substr($searchTitle, 0, self::MIN_PARTIAL_LENGTH))) {
            $score += 15;
        }

        if (strlen($searchArtists) >= self::MIN_PARTIAL_LENGTH && str_contains($trackUser, substr($searchArtists, 0, self::MIN_PARTIAL_LENGTH))) {
            $score += 10;
        }

        return $score;
    }

    private function cleanSearchTerm(string $term): string
    {
        $term = preg_replace('/\s*\([^)]*\)/', '', $term) ?? '';
        $term = preg_replace('/\s*feat\.?\s*/i', ' ', $term) ?? '';
        $term = preg_replace('/\s*ft\.?\s*/i', ' ', $term) ?? '';
        $term = preg_replace('/\s*featuring\s*/i', ' ', $term) ?? '';
        $term = preg_replace('/\s*\(feat\.?\s*[^)]*\)/i', '', $term) ?? '';

        return trim($term);
    }

    private function extractMainArtist(string $artists): string
    {
        // Prendre le premier artiste (avant la virgule)
        $mainArtist = explode(',', $artists)[0];

        return trim($mainArtist);
    }

    private function addTrackToPlaylist(Provider $provider, int $playlistId, int $trackId): void
    {
        $maxRetries = 3;
        $retryDelay = 1; // 1 seconde entre chaque retry

        for ($attempt = 1; $attempt <= $maxRetries; ++$attempt) {
            try {
                // SoundCloud requires GET + PUT approach with complete playlist data
                $playlistData = $this->makeAuthenticatedRequest(
                    $provider,
                    'GET',
                    self::API_BASE_URL.'/playlists/'.$playlistId
                );

                /** @var array<int, array<string, mixed>> $currentTracks */
                $currentTracks = $playlistData['tracks'] ?? [];

                // Clean tracks to only keep the ID (SoundCloud API requirement)
                $cleanTracks = [];
                foreach ($currentTracks as $track) {
                    if (isset($track['id'])) {
                        $cleanTracks[] = ['id' => $track['id']];
                    }
                }

                // Add the new track to the existing tracks array
                $cleanTracks[] = ['id' => $trackId];

                $putData = [
                    'playlist' => [
                        'title' => $playlistData['title'] ?? 'MixLink Playlist',
                        'description' => $playlistData['description'] ?? 'Created with MixLink',
                        'sharing' => $playlistData['sharing'] ?? 'private',
                        'tracks' => $cleanTracks,
                    ],
                ];

                $this->makeAuthenticatedRequest(
                    $provider,
                    'PUT',
                    self::API_BASE_URL.'/playlists/'.$playlistId,
                    [
                        'json' => $putData,
                    ]
                );

                // Si on arrive ici, l'ajout a réussi
                return;
            } catch (RuntimeException $e) {
                $this->logger->error("SoundCloud API Error (attempt $attempt/$maxRetries): ".$e->getMessage());

                // Si c'est la dernière tentative, on relance l'exception
                if ($attempt === $maxRetries) {
                    throw $e;
                }

                // Sinon, on attend avant de retenter
                $this->logger->info("Retrying in {$retryDelay} second(s)...");
                sleep($retryDelay);

                // Augmenter le délai pour le prochain retry (backoff exponentiel)
                $retryDelay *= 2;
            }
        }
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
            'Authorization' => 'OAuth '.$accessToken,
            'Content-Type' => 'application/json',
        ]);

        try {
            $response = $this->httpClient->request($method, $url, $options);

            if (Response::HTTP_OK === $response->getStatusCode() || Response::HTTP_CREATED === $response->getStatusCode()) {
                /** @var array<string, mixed> */
                return $response->toArray(false);
            }

            // Get the actual error message from SoundCloud API
            $errorContent = $response->toArray(false);
            $errorMessage = 'Unknown error';

            if (isset($errorContent['error']['message']) && is_string($errorContent['error']['message'])) {
                $errorMessage = $errorContent['error']['message'];
            } elseif (isset($errorContent['error']['errors']) && is_array($errorContent['error']['errors'])) {
                $encodedErrors = json_encode($errorContent['error']['errors']);
                $errorMessage = is_string($encodedErrors) ? $encodedErrors : 'Unknown error';
            } elseif (isset($errorContent['message']) && is_string($errorContent['message'])) {
                $errorMessage = $errorContent['message'];
            }

            throw new RuntimeException('SoundCloud API request failed ('.$response->getStatusCode().'): '.$errorMessage);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), '401')) {
                try {
                    $newAccessToken = $this->tokenManager->refreshAccessToken($provider);

                    $options['headers']['Authorization'] = 'OAuth '.$newAccessToken;
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
