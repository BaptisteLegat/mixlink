<?php

namespace App\Tests\Unit\Provider;

use App\ApiResource\ApiReference;
use App\Entity\Provider;
use App\Entity\User;
use App\Provider\ProviderMapper;
use App\Provider\ProviderModel;
use App\Security\OAuthUserData;
use Kerox\OAuth2\Client\Provider\SpotifyResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use Martin1982\OAuth2\Client\Provider\SoundCloudResourceOwner;
use PHPUnit\Framework\TestCase;

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
            'email' => 'test@gmail.com',
        ]);

        $oauthUserData = new OAuthUserData($google, 'access_token', 'refresh_token');

        $providerName = ApiReference::GOOGLE;
        $user = (new User())->setEmail('test@gmail.com');
        $existingProvider = null;

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame($providerName, $provider->getName());
        $this->assertSame($user, $provider->getUser());
        $this->assertSame($oauthUserData->getAccessToken(), $provider->getAccessToken());
        $this->assertSame($oauthUserData->getRefreshToken(), $provider->getRefreshToken());
        $this->assertSame('1234567890', $provider->getProviderUserId());
        $this->assertSame('test@gmail.com', $provider->getUser()->getEmail());
    }

    public function testMapEntitySpotify(): void
    {
        $spotify = new SpotifyResourceOwner([
            'sub' => '1234567890',
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
            'id' => '1234567890',
        ]);

        $oauthUserData = new OAuthUserData($spotify, 'access_token', 'refresh_token');

        $providerName = ApiReference::SPOTIFY;
        $user = (new User())->setEmail('test@gmail.com');
        $existingProvider = null;

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame($providerName, $provider->getName());
        $this->assertSame($user, $provider->getUser());
        $this->assertSame($oauthUserData->getAccessToken(), $provider->getAccessToken());
        $this->assertSame($oauthUserData->getRefreshToken(), $provider->getRefreshToken());
        $this->assertSame('1234567890', $provider->getProviderUserId());
        $this->assertSame('test@gmail.com', $provider->getUser()->getEmail());
    }

    public function testMapEntitySoundCloud(): void
    {
        $soundcloud = new SoundCloudResourceOwner([
            'id' => 'sc_123',
            'full_name' => 'SC User',
            'avatar_url' => 'http://soundcloud.com/avatar.jpg',
        ]);

        $oauthUserData = new OAuthUserData($soundcloud, 'access_token', 'refresh_token');

        $providerName = ApiReference::SOUNDCLOUD;
        $user = (new User())->setEmail('soundcloud@test.com');
        $existingProvider = null;

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame($providerName, $provider->getName());
        $this->assertSame($user, $provider->getUser());
        $this->assertSame($oauthUserData->getAccessToken(), $provider->getAccessToken());
        $this->assertSame($oauthUserData->getRefreshToken(), $provider->getRefreshToken());
        $this->assertSame('sc_123', $provider->getProviderUserId());
        $this->assertSame('soundcloud@test.com', $provider->getUser()->getEmail());
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
        $spotify = new SpotifyResourceOwner([
            'sub' => '1234567890',
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
            'id' => '1234567890',
        ]);

        $oauthUserData = new OAuthUserData($spotify, 'access_token', 'refresh_token');

        $providerName = ApiReference::SPOTIFY;
        $user = (new User())->setEmail('test@gmail.com');
        $existingProvider = (new Provider())
            ->setName($providerName)
            ->setUser($user)
            ->setProviderUserId('1234567890')
            ->setAccessToken('old_access_token')
        ;

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame($providerName, $provider->getName());
        $this->assertSame($user, $provider->getUser());
        $this->assertSame($oauthUserData->getAccessToken(), $provider->getAccessToken());
        $this->assertSame($oauthUserData->getRefreshToken(), $provider->getRefreshToken());
    }

    public function testMapEntityWithExistingProviderSoundCloud(): void
    {
        $soundcloud = new SoundCloudResourceOwner([
            'id' => 'sc_456',
            'full_name' => 'SC User 2',
            'avatar_url' => 'http://soundcloud.com/avatar2.jpg',
        ]);

        $oauthUserData = new OAuthUserData($soundcloud, 'access_token', 'refresh_token');

        $providerName = ApiReference::SOUNDCLOUD;
        $user = (new User())->setEmail('soundcloud2@test.com');
        $existingProvider = (new Provider())
            ->setName($providerName)
            ->setUser($user)
            ->setAccessToken('old_access_token')
            ->setProviderUserId('test')
        ;

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame($providerName, $provider->getName());
        $this->assertSame($user, $provider->getUser());
        $this->assertSame($oauthUserData->getAccessToken(), $provider->getAccessToken());
        $this->assertSame($oauthUserData->getRefreshToken(), $provider->getRefreshToken());
        $this->assertSame('sc_456', $provider->getProviderUserId());
    }

    public function testMapModel(): void
    {
        $name = ApiReference::GOOGLE;
        $accessToken = 'test_access_token';
        $refreshToken = 'test_refresh_token';

        $provider = new Provider()
            ->setName($name)
            ->setAccessToken($accessToken)
            ->setRefreshToken($refreshToken)
        ;

        $providerModel = $this->providerMapper->mapModel($provider);

        $this->assertInstanceOf(ProviderModel::class, $providerModel);
        $this->assertEquals($name, $providerModel->getName());
        $this->assertFalse($providerModel->isMain());
    }

    public function testMapModelWithCurrentAccessToken(): void
    {
        $name = ApiReference::GOOGLE;
        $accessToken = 'test_access_token';
        $refreshToken = 'test_refresh_token';
        $currentAccessToken = 'test_access_token';

        $provider = new Provider()
            ->setName($name)
            ->setAccessToken($accessToken)
            ->setRefreshToken($refreshToken)
        ;

        $providerModel = $this->providerMapper->mapModel($provider, $currentAccessToken);

        $this->assertInstanceOf(ProviderModel::class, $providerModel);
        $this->assertEquals($name, $providerModel->getName());
        $this->assertTrue($providerModel->isMain());
    }

    public function testMapModelWithDifferentAccessToken(): void
    {
        $name = ApiReference::GOOGLE;
        $accessToken = 'test_access_token';
        $refreshToken = 'test_refresh_token';
        $currentAccessToken = 'different_access_token';

        $provider = new Provider()
            ->setName($name)
            ->setAccessToken($accessToken)
            ->setRefreshToken($refreshToken)
        ;

        $providerModel = $this->providerMapper->mapModel($provider, $currentAccessToken);

        $this->assertInstanceOf(ProviderModel::class, $providerModel);
        $this->assertEquals($name, $providerModel->getName());
        $this->assertFalse($providerModel->isMain());
    }

    public function testMapModelWithNullValues(): void
    {
        $name = ApiReference::SPOTIFY;

        $provider = new Provider()
            ->setName($name)
            ->setAccessToken(null)
            ->setRefreshToken(null)
        ;

        $providerModel = $this->providerMapper->mapModel($provider);

        $this->assertInstanceOf(ProviderModel::class, $providerModel);
        $this->assertEquals($name, $providerModel->getName());
        $this->assertFalse($providerModel->isMain());
    }

    public function testMapModelWithEmptyValues(): void
    {
        $name = ApiReference::GOOGLE;
        $accessToken = '';
        $refreshToken = '';

        $provider = new Provider()
            ->setName($name)
            ->setAccessToken($accessToken)
            ->setRefreshToken($refreshToken)
        ;

        $providerModel = $this->providerMapper->mapModel($provider);

        $this->assertInstanceOf(ProviderModel::class, $providerModel);
        $this->assertEquals($name, $providerModel->getName());
        $this->assertFalse($providerModel->isMain());
    }

    public function testMapModelWithNullCurrentAccessToken(): void
    {
        $name = ApiReference::GOOGLE;
        $accessToken = 'test_access_token';
        $refreshToken = 'test_refresh_token';
        $currentAccessToken = null;

        $provider = new Provider()
            ->setName($name)
            ->setAccessToken($accessToken)
            ->setRefreshToken($refreshToken)
        ;

        $providerModel = $this->providerMapper->mapModel($provider, $currentAccessToken);

        $this->assertInstanceOf(ProviderModel::class, $providerModel);
        $this->assertEquals($name, $providerModel->getName());
        $this->assertFalse($providerModel->isMain());
    }
}
