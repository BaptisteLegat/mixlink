<?php

namespace App\Provider;

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

        $this->setTimestampable($provider, $existingProvider !== null);
        $this->setBlameable($provider, $user->getEmail(), $existingProvider !== null);

        $this->providerRepository->save($provider, true);
    }
}
