<?php

namespace App\Provider;

use App\Entity\Provider;
use App\Entity\User;
use App\Security\OAuthUserData;

class ProviderMapper
{
    public function mapEntity(
        OAuthUserData $oauthUserData,
        string $providerName,
        User $user,
        ?Provider $provider,
    ): Provider {
        if (!$provider) {
            $provider = new Provider();
        }

        $provider->setName($providerName);
        $provider->setAccessToken($oauthUserData->getAccessToken());
        $provider->setRefreshToken($oauthUserData->getRefreshToken());
        $provider->setUser($user);

        $resourceOwner = $oauthUserData->getUser();
        $provider->setProviderUserId((string) $resourceOwner->getId());

        return $provider;
    }

    public function mapModel(Provider $provider, ?string $currentAccessToken = null): ProviderModel
    {
        $providerModel = new ProviderModel();
        $accessToken = $provider->getAccessToken();

        $isMain = null !== $currentAccessToken && $accessToken === $currentAccessToken;

        $providerModel
            ->setId((string) $provider->getId())
            ->setName($provider->getName())
            ->setIsMain($isMain)
        ;

        return $providerModel;
    }
}
