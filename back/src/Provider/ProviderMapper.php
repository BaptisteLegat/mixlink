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

        return $provider;
    }
}
