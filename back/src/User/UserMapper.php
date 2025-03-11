<?php

namespace App\User;

use App\ApiResource\ApiReference;
use App\Entity\User;
use InvalidArgumentException;
use Kerox\OAuth2\Client\Provider\SpotifyResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class UserMapper
{
    private const array PROVIDER_MAPPERS = [
        ApiReference::GOOGLE => 'mapGoogleUser',
        ApiReference::SPOTIFY => 'mapSpotifyUser',
    ];

    public function mapEntity(ResourceOwnerInterface $resourceOwner, string $providerName, ?User $user): User
    {
        $user ??= new User();

        if (!isset(self::PROVIDER_MAPPERS[$providerName])) {
            throw new InvalidArgumentException("Provider $providerName not supported");
        }

        $method = self::PROVIDER_MAPPERS[$providerName];

        $this->$method($resourceOwner, $user);

        $user->setRoles(['ROLE_USER']);

        return $user;
    }

    private function mapGoogleUser(GoogleUser $resourceOwner, User $user): void
    {
        $user->setFirstName((string) $resourceOwner->getFirstName());
        $user->setLastName((string) $resourceOwner->getLastName());
        $user->setEmail((string) $resourceOwner->getEmail());
        $user->setProfilePicture($resourceOwner->getAvatar());
    }

    private function mapSpotifyUser(SpotifyResourceOwner $resourceOwner, User $user): void
    {
        $user->setFirstName($resourceOwner->getDisplayName());
        $user->setEmail((string) $resourceOwner->getEmail());

        /** @var array<int, array{url: string}> $images */
        $images = $resourceOwner->getImages();

        if (!empty($images) && isset($images[0]['url'])) {
            $user->setProfilePicture($images[0]['url']);
        }
    }
}
