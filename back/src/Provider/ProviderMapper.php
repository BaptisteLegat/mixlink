<?php

namespace App\Provider;

use App\Entity\Provider;
use App\Entity\User;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class ProviderMapper
{
    // public function mapEntity(ResourceOwnerInterface $resourceOwner,string $provider, ?User $user): void
    // {
    //     if (!$user) {
    //         $user = new User();
    //     }

    //     $user->setProvider($provider);
    //     $user->setProviderId($resourceOwner->getId());
    //     $user->setAccessToken($resourceOwner->getToken()->getToken());
    //     $user->setRefreshToken($resourceOwner->getToken()->getRefreshToken());
    // }
}
