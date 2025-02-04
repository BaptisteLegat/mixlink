<?php

namespace App\User;

use App\ApiResource\ApiReference;
use App\Entity\User;
use App\Provider\ProviderManager;
use InvalidArgumentException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class UserMapper
{
    public function mapEntity(ResourceOwnerInterface $resourceOwner, string $providerName, ?User $user): User
    {
        if (!$user) {
            $user = new User();
        }

        match ($providerName) {
            ApiReference::GOOGLE => $this->mapGoogleUser($resourceOwner, $user),
            ApiReference::SPOTIFY => $this->mapSpotifyUser($resourceOwner, $user),
            default => throw new InvalidArgumentException("Provider $providerName not supported"),
        };

        $user->setRoles(['ROLE_USER']);

        return $user;
    }

    private function mapGoogleUser(ResourceOwnerInterface $resourceOwner, User $user): void
    {
        $user->setFirstName($resourceOwner->getFirstName());
        $user->setLastName($resourceOwner->getLastName());
        $user->setEmail($resourceOwner->getEmail());
        $user->setProfilePicture($resourceOwner->getAvatar());
    }

    private function mapSpotifyUser(ResourceOwnerInterface $resourceOwner, User $user): void
    {
        $user->setFirstName($resourceOwner->getDisplayName());
        $user->setEmail($resourceOwner->getEmail());

        $images = $resourceOwner->getImages();
        if (!empty($images)) {
            $user->setProfilePicture($images[0]['url']);
        }
    }
}
