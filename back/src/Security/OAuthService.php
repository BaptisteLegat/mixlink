<?php

namespace App\Security;

use InvalidArgumentException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OAuthService
{
    public const GOOGLE_SCOPES = [
        'https://www.googleapis.com/auth/youtube', // Accès à YouTube
        'https://www.googleapis.com/auth/userinfo.email', // Accès à l'email
        'https://www.googleapis.com/auth/userinfo.profile', // Accès au profil
    ];

    public const SPOTIFY_SCOPES = [
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

    public function fetchUser(string $provider): ResourceOwnerInterface
    {
        $client = $this->clientRegistry->getClient($provider);

        return $client->fetchUser();
    }
}
