<?php

namespace App\Security;

use App\ApiResource\ApiReference;
use App\Security\Provider\SoundCloudUserData;
use Exception;
use InvalidArgumentException;
use Kerox\OAuth2\Client\Provider\SpotifyScope;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OAuthService
{
    public const array GOOGLE_SCOPES = [
        'https://www.googleapis.com/auth/youtube',
        'https://www.googleapis.com/auth/youtube.force-ssl',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
    ];

    public const array SPOTIFY_SCOPES = [
        SpotifyScope::USER_READ_EMAIL->value,
        SpotifyScope::USER_READ_PRIVATE->value,
        SpotifyScope::PLAYLIST_MODIFY_PUBLIC->value,
        SpotifyScope::PLAYLIST_MODIFY_PRIVATE->value,
    ];

    public const array SOUNDCLOUD_SCOPES = [
        'non-expiring',
    ];

    public function __construct(
        private ClientRegistry $clientRegistry,
        private LoggerInterface $logger,
    ) {
    }

    public function getRedirectResponse(string $provider): RedirectResponse
    {
        $client = $this->clientRegistry->getClient($provider);

        $scopes = match ($provider) {
            ApiReference::GOOGLE => self::GOOGLE_SCOPES,
            ApiReference::SPOTIFY => self::SPOTIFY_SCOPES,
            ApiReference::SOUNDCLOUD => self::SOUNDCLOUD_SCOPES,
            default => throw new InvalidArgumentException("Provider $provider not supported"),
        };

        $options = [];

        if (ApiReference::GOOGLE === $provider) {
            $options = [
                'access_type' => 'offline',
                'prompt' => 'consent',
            ];
        }

        return $client->redirect($scopes, $options);
    }

    public function fetchUser(string $provider): OAuthUserData
    {
        try {
            $client = $this->clientRegistry->getClient($provider);

            $accessToken = $client->getAccessToken();

            $user = $client->fetchUserFromToken($accessToken);

            if (ApiReference::SOUNDCLOUD === $provider) {
                /** @var array<string, mixed> $userData */
                $userData = $user->toArray();
                $user = new SoundCloudUserData($userData);
            }

            return new OAuthUserData(
                $user,
                $accessToken->getToken(),
                $accessToken->getRefreshToken()
            );
        } catch (Exception $e) {
            $this->logger->error('OAuth Error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
