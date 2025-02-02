<?php

namespace App\User;

use App\Entity\User;
use App\Provider\ProviderMapper;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class UserMapper
{
    public function __construct(private ProviderMapper $providerMapper)
    {
    }

    public function mapEntity(ResourceOwnerInterface $resourceOwner, string $provider, ?User $user): void
    {
        // if (!$user) {
        //     $user = new User();
        // }

        // $user->setFirstName($resourceOwner->getFirstName());
        // $user->setLastName($resourceOwner->getLastName());
        // $user->setEmail($resourceOwner->getEmail());

        // $this->providerMapper->mapEntity($resourceOwner, $provider, $user);
    }
}
