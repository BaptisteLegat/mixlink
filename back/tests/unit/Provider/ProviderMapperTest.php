<?php

namespace App\Tests\Unit\Provider;

use App\ApiResource\ApiReference;
use App\Entity\Provider;
use App\Entity\User;
use App\Provider\ProviderMapper;
use App\Security\OAuthUserData;
use League\OAuth2\Client\Provider\GoogleUser;
use Monolog\Test\TestCase;

class ProviderMapperTest extends TestCase
{
    private ProviderMapper $providerMapper;

    protected function setUp(): void
    {
        $this->providerMapper = new ProviderMapper();
    }

    public function testMapEntityGoogle(): void
    {
        $google = new GoogleUser([
            'sub' => '1234567890',
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
        ]);

        $oauthUserData = new OAuthUserData($google, 'access_token');

        $providerName = ApiReference::GOOGLE;
        $user = (new User())->setEmail('test@gmail.com');
        $existingProvider = null;

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame($providerName, $provider->getName());
        $this->assertSame($user, $provider->getUser());
        $this->assertSame($oauthUserData->getAccessToken(), $provider->getAccessToken());
        $this->assertSame($oauthUserData->getRefreshToken(), $provider->getRefreshToken());
    }

    public function testMapEntitySpotify(): void
    {
        $google = new GoogleUser([
            'sub' => '1234567890',
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
        ]);

        $oauthUserData = new OAuthUserData($google, 'access_token');

        $providerName = ApiReference::SPOTIFY;
        $user = (new User())->setEmail('test@gmail.com');
        $existingProvider = null;

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame($providerName, $provider->getName());
        $this->assertSame($user, $provider->getUser());
        $this->assertSame($oauthUserData->getAccessToken(), $provider->getAccessToken());
        $this->assertSame($oauthUserData->getRefreshToken(), $provider->getRefreshToken());
    }

    public function testMapEntityWithExistingProviderGoogle(): void
    {
        $google = new GoogleUser([
            'sub' => '1234567890',
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
        ]);

        $oauthUserData = new OAuthUserData($google, 'access_token');

        $providerName = ApiReference::GOOGLE;
        $user = (new User())->setEmail('test@gmail.com');
        $existingProvider = (new Provider())
            ->setName($providerName)
            ->setUser($user)
            ->setName($google->getName())
            ->setAccessToken('old_access_token')
        ;

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame($providerName, $provider->getName());
        $this->assertSame($user, $provider->getUser());
        $this->assertSame($oauthUserData->getAccessToken(), $provider->getAccessToken());
        $this->assertSame($oauthUserData->getRefreshToken(), $provider->getRefreshToken());
    }

    public function testMapEntityWithExistingProviderSpotify(): void
    {
        $google = new GoogleUser([
            'sub' => '1234567890',
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
        ]);

        $oauthUserData = new OAuthUserData($google, 'access_token');

        $providerName = ApiReference::SPOTIFY;
        $user = (new User())->setEmail('test@gmail.com');
        $existingProvider = (new Provider())
            ->setName($providerName)
            ->setUser($user)
            ->setName($google->getName())
            ->setAccessToken('old_access_token')
        ;

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame($providerName, $provider->getName());
        $this->assertSame($user, $provider->getUser());
        $this->assertSame($oauthUserData->getAccessToken(), $provider->getAccessToken());
        $this->assertSame($oauthUserData->getRefreshToken(), $provider->getRefreshToken());
    }
}
