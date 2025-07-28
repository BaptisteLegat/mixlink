<?php

namespace App\Service;

use App\Service\Model\SpotifyTrack;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyService
{
    private const string TOKEN_CACHE_KEY = 'spotify_app_token';
    private const string TOKEN_URL = 'https://accounts.spotify.com/api/token';
    private const string SEARCH_URL = 'https://api.spotify.com/v1/search';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        #[Autowire('%spotify_client_id%')]
        private string $clientId,
        #[Autowire('%spotify_client_secret%')]
        private string $clientSecret,
    ) {
    }

    /**
     * @return SpotifyTrack[]
     */
    public function searchTracks(string $query): array
    {
        $token = $this->getAppToken();
        $response = $this->httpClient->request('GET', self::SEARCH_URL, [
            'query' => [
                'q' => $query,
                'type' => 'track',
                'limit' => 10,
            ],
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new RuntimeException('The Spotify API request failed');
        }

        /** @var array<array-key, mixed> $data */
        $data = $response->toArray(false);

        return $this->extractTracksFromData($data);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return SpotifyTrack[]
     */
    private function extractTracksFromData(array $data): array
    {
        $items = $this->getTrackItems($data);

        if (empty($items)) {
            return [];
        }

        return array_map([$this, 'createSpotifyTrack'], $items);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTrackItems(array $data): array
    {
        if (!isset($data['tracks']) || !is_array($data['tracks'])) {
            return [];
        }

        $tracks = $data['tracks'];
        if (!isset($tracks['items']) || !is_array($tracks['items'])) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $items */
        $items = $tracks['items'];

        return $items;
    }

    /**
     * @param array<string, mixed> $track
     */
    private function createSpotifyTrack(array $track): SpotifyTrack
    {
        return new SpotifyTrack(
            $this->extractStringValue($track, 'id'),
            $this->extractStringValue($track, 'name'),
            $this->extractArtists($track),
            $this->extractAlbumImage($track),
            $this->extractStringValue($track, 'preview_url'),
            $this->extractExternalUrl($track)
        );
    }

    /**
     * @param array<string, mixed> $track
     *
     * @return string[]
     */
    private function extractArtists(array $track): array
    {
        if (!isset($track['artists']) || !is_array($track['artists'])) {
            return [];
        }

        $artists = [];

        /** @var array<int, array<string, mixed>> $artistsData */
        $artistsData = $track['artists'];
        foreach ($artistsData as $artist) {
            $artists[] = $this->extractStringValue($artist, 'name');
        }

        return $artists;
    }

    /**
     * @param array<string, mixed> $track
     */
    private function extractAlbumImage(array $track): ?string
    {
        if (!isset($track['album']) || !is_array($track['album'])) {
            return null;
        }

        $album = $track['album'];
        if (!isset($album['images']) || !is_array($album['images'])) {
            return null;
        }

        $images = $album['images'];
        if (!isset($images[0]) || !is_array($images[0])) {
            return null;
        }

        $firstImage = $images[0];

        return isset($firstImage['url']) && is_string($firstImage['url']) ? $firstImage['url'] : null;
    }

    /**
     * @param array<string, mixed> $track
     */
    private function extractExternalUrl(array $track): ?string
    {
        if (!isset($track['external_urls']) || !is_array($track['external_urls'])) {
            return null;
        }

        $externalUrls = $track['external_urls'];

        return isset($externalUrls['spotify']) && is_string($externalUrls['spotify'])
            ? $externalUrls['spotify']
            : null
        ;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractStringValue(array $data, string $key): string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : '';
    }

    /**
     * Get the Spotify application token (Client Credentials Flow), using Symfony cache.
     * The token is automatically renewed if expired (lifetime: 1 hour, with a safety margin).
     */
    private function getAppToken(): string
    {
        $token = $this->cache->get(self::TOKEN_CACHE_KEY, function (ItemInterface $item): string {
            $item->expiresAfter(3500); // 1 hour - safety margin
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);
            if (Response::HTTP_OK !== $response->getStatusCode()) {
                throw new RuntimeException('The Spotify token request failed');
            }
            $data = $response->toArray(false);

            return isset($data['access_token']) && is_string($data['access_token']) ? $data['access_token'] : '';
        });

        return $token;
    }
}
