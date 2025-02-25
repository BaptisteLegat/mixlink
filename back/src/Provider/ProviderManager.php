<?php

namespace App\Provider;

use App\Entity\Provider;
use App\Entity\User;
use App\Repository\ProviderRepository;
use App\Security\OAuthUserData;
use App\Trait\TraceableTrait;

class ProviderManager
{
    use TraceableTrait;

    public function __construct(private ProviderRepository $providerRepository, private ProviderMapper $providerMapper)
    {
    }

    public function createOrUpdateProvider(OAuthUserData $oauthUserData, string $providerName, User $user): void
    {
        $existingProvider = $this->providerRepository->findOneBy(['name' => $providerName, 'user' => $user]);

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $isUpdate = $existingProvider instanceof Provider;

        $this->setTimestampable($provider, $isUpdate);
        $this->setBlameable($provider, $user->getEmail(), $isUpdate);

        $this->providerRepository->save($provider, true);
    }

    public function findByAccessToken(string $accessToken): ?User
    {
        return $this->providerRepository->findOneBy(['accessToken' => $accessToken])?->getUser();
    }
}
