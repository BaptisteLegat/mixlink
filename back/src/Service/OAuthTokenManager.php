<?php

namespace App\Service;

use App\Entity\Provider;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OAuthTokenManager
{
    private const string GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const string SPOTIFY_TOKEN_URL = 'https://accounts.spotify.com/api/token';
    private const string SOUNDCLOUD_TOKEN_URL = 'https://api.soundcloud.com/oauth2/token';

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        #[Autowire('%google_client_id%')]
        private string $googleClientId,
        #[Autowire('%google_client_secret%')]
        private string $googleClientSecret,
        #[Autowire('%spotify_client_id%')]
        private string $spotifyClientId,
        #[Autowire('%spotify_client_secret%')]
        private string $spotifyClientSecret,
        #[Autowire('%soundcloud_client_id%')]
        private string $soundcloudClientId,
        #[Autowire('%soundcloud_client_secret%')]
        private string $soundcloudClientSecret,
    ) {
    }

    public function getValidAccessToken(Provider $provider): string
    {
        $accessToken = $provider->getAccessToken();
        $refreshToken = $provider->getRefreshToken();

        if (null === $accessToken) {
            throw new RuntimeException('No access token available');
        }

        if (null === $refreshToken) {
            return $accessToken;
        }

        return $accessToken;
    }

    public function hasRefreshToken(Provider $provider): bool
    {
        return null !== $provider->getRefreshToken();
    }

    public function refreshAccessToken(Provider $provider): string
    {
        $refreshToken = $provider->getRefreshToken();
        if (null === $refreshToken) {
            throw new RuntimeException('No refresh token available for this provider. Please reconnect to get a refresh token.');
        }

        $tokenData = match ($provider->getName()) {
            'google' => $this->refreshGoogleToken($refreshToken),
            'spotify' => $this->refreshSpotifyToken($refreshToken),
            'soundcloud' => $this->refreshSoundCloudToken($refreshToken),
            default => throw new RuntimeException("Unsupported provider: {$provider->getName()}"),
        };

        $provider->setAccessToken($tokenData['access_token']);
        if (isset($tokenData['refresh_token'])) {
            $provider->setRefreshToken($tokenData['refresh_token']);
        }

        $this->entityManager->flush();

        return $tokenData['access_token'];
    }

    /**
     * @return array{access_token: string, refresh_token?: string}
     */
    private function refreshGoogleToken(string $refreshToken): array
    {
        $response = $this->httpClient->request('POST', self::GOOGLE_TOKEN_URL, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'client_id' => $this->googleClientId,
                'client_secret' => $this->googleClientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ],
        ]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new RuntimeException('Failed to refresh Google token');
        }

        /** @var array{access_token: string, refresh_token?: string} */
        $data = $response->toArray(false);

        return $data;
    }

    /**
     * @return array{access_token: string, refresh_token?: string}
     */
    private function refreshSpotifyToken(string $refreshToken): array
    {
        $response = $this->httpClient->request('POST', self::SPOTIFY_TOKEN_URL, [
            'headers' => [
                'Authorization' => 'Basic '.base64_encode($this->spotifyClientId.':'.$this->spotifyClientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new RuntimeException('Failed to refresh Spotify token');
        }

        /** @var array{access_token: string, refresh_token?: string} */
        $data = $response->toArray(false);

        return $data;
    }

    /**
     * @return array{access_token: string, refresh_token?: string}
     */
    private function refreshSoundCloudToken(string $refreshToken): array
    {
        $response = $this->httpClient->request('POST', self::SOUNDCLOUD_TOKEN_URL, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'client_id' => $this->soundcloudClientId,
                'client_secret' => $this->soundcloudClientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new RuntimeException('Failed to refresh SoundCloud token');
        }

        /** @var array{access_token: string, refresh_token?: string} */
        $data = $response->toArray(false);

        return $data;
    }
}
