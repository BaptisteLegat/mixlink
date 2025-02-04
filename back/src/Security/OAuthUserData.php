<?php

namespace App\Security;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class OAuthUserData
{
    public function __construct(
        private ResourceOwnerInterface $user,
        private string $accessToken,
        private ?string $refreshToken = null
    ) {}

    public function getUser(): ResourceOwnerInterface
    {
        return $this->user;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }
}
