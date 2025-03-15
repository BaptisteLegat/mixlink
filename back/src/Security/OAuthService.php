<?php

namespace App\Security;

use InvalidArgumentException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OAuthService
{
    public const array GOOGLE_SCOPES = [
        'https://www.googleapis.com/auth/youtube', // Access to YouTube
        'https://www.googleapis.com/auth/userinfo.email', // Access to email
        'https://www.googleapis.com/auth/userinfo.profile', // Access to profile
    ];

    public const array SPOTIFY_SCOPES = [
        'user-read-email',
        'user-read-private',
    ];

    public function __construct(private ClientRegistry $clientRegistry)
    {
    }

    /**
     * Get the redirect response for the given provider.
     */
    public function getRedirectResponse(string $provider): RedirectResponse
    {
        $client = $this->clientRegistry->getClient($provider);

        $scopes = match ($provider) {
            'google' => self::GOOGLE_SCOPES,
            'spotify' => self::SPOTIFY_SCOPES,
            default => throw new InvalidArgumentException("Provider $provider not supported"),
        };

        return $client->redirect($scopes, []);
    }

    public function fetchUser(string $provider): OAuthUserData
    {
        $client = $this->clientRegistry->getClient($provider);

        $accessToken = $client->getAccessToken();

        $user = $client->fetchUserFromToken($accessToken);

        return new OAuthUserData(
            user: $user,
            accessToken: $accessToken->getToken(),
            refreshToken: $accessToken->getRefreshToken()
        );
    }
}
