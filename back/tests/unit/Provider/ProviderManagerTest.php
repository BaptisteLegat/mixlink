<?php

namespace App\Tests\Unit\Provider;

use App\ApiResource\ApiReference;
use App\Entity\Provider;
use App\Entity\User;
use App\Provider\ProviderManager;
use App\Provider\ProviderMapper;
use App\Repository\ProviderRepository;
use App\Security\OAuthUserData;
use League\OAuth2\Client\Provider\GoogleUser;
use Monolog\Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ProviderManagerTest extends TestCase
{
    private ProviderRepository|MockObject $providerRepositoryMocked;
    private ProviderMapper|MockObject $providerMapperMocked;
    private ProviderManager $providerManager;

    protected function setUp(): void
    {
        $this->providerRepositoryMocked = $this->createMock(ProviderRepository::class);
        $this->providerMapperMocked = $this->createMock(ProviderMapper::class);

        $this->providerManager = new ProviderManager(
            $this->providerRepositoryMocked,
            $this->providerMapperMocked
        );
    }

    public function testCreateProviderGoogle(): void
    {
        $providerName = ApiReference::GOOGLE;
        $user = (new User())->setEmail('test@gmail.com');

        $oauthUserData = new OAuthUserData(new GoogleUser([
                'sub' => '1234567890',
                'name' => 'John Doe',
                'email' => 'test@gmail.com',
            ]), 'access_token')
        ;

        $existingProvider = null;
        $expectedProvider = new Provider();

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $providerName, 'user' => $user])
            ->willReturn($existingProvider)
        ;

        $this->providerMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($oauthUserData, $providerName, $user, $existingProvider)
            ->willReturn($expectedProvider)
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('save')
            ->with($expectedProvider, true)
        ;

        $this->providerManager->createOrUpdateProvider($oauthUserData, $providerName, $user);

        $this->assertInstanceOf(Provider::class, $expectedProvider);
    }

    public function testCreateProviderSpotify(): void
    {
        $providerName = ApiReference::SPOTIFY;
        $user = (new User())->setEmail('test@gmail.com');

        $oauthUserData = new OAuthUserData(new GoogleUser([
                'sub' => '1234567890',
                'name' => 'John Doe',
                'email' => 'test@gmail.com',
            ]), 'access_token')
        ;

        $existingProvider = null;
        $expectedProvider = new Provider();

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $providerName, 'user' => $user])
            ->willReturn($existingProvider)
        ;

        $this->providerMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($oauthUserData, $providerName, $user, $existingProvider)
            ->willReturn($expectedProvider)
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('save')
            ->with($expectedProvider, true)
        ;

        $this->providerManager->createOrUpdateProvider($oauthUserData, $providerName, $user);

        $this->assertInstanceOf(Provider::class, $expectedProvider);
    }

    public function testUpdateProviderGoogle(): void
    {
        $providerName = ApiReference::GOOGLE;
        $user = (new User())->setEmail('test@gmail.com');

        $oauthUserData = new OAuthUserData(new GoogleUser([
                'sub' => '1234567890',
                'name' => 'John Doe',
                'email' => 'test@gmail.com',
            ]), 'access_token')
        ;

        $existingProvider = new Provider();
        $expectedProvider = new Provider();

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $providerName, 'user' => $user])
            ->willReturn($existingProvider)
        ;

        $this->providerMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($oauthUserData, $providerName, $user, $existingProvider)
            ->willReturn($expectedProvider)
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('save')
            ->with($expectedProvider, true)
        ;

        $this->providerManager->createOrUpdateProvider($oauthUserData, $providerName, $user);

        $this->assertInstanceOf(Provider::class, $expectedProvider);
    }

    public function testUpdateProviderSpotify(): void
    {
        $providerName = ApiReference::SPOTIFY;
        $user = (new User())->setEmail('test@gmail.com');

        $oauthUserData = new OAuthUserData(new GoogleUser([
                'sub' => '1234567890',
                'name' => 'John Doe',
                'email' => 'test@gmail.com',
            ]), 'access_token')
        ;

        $existingProvider = new Provider();
        $expectedProvider = new Provider();

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $providerName, 'user' => $user])
            ->willReturn($existingProvider)
        ;

        $this->providerMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($oauthUserData, $providerName, $user, $existingProvider)
            ->willReturn($expectedProvider)
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('save')
            ->with($expectedProvider, true)
        ;

        $this->providerManager->createOrUpdateProvider($oauthUserData, $providerName, $user);

        $this->assertInstanceOf(Provider::class, $expectedProvider);
    }

    public function testFindByAccessToken(): void
    {
        $accessToken = 'access_token';
        $user = new User();
        $provider = (new Provider())->setUser($user)->setAccessToken($accessToken);

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['accessToken' => $accessToken])
            ->willReturn($provider)
        ;

        $result = $this->providerManager->findByAccessToken($accessToken);

        $this->assertSame($user, $result);
    }

    public function testFindByAccessTokenReturnsNullWhenNotFound(): void
    {
        $accessToken = 'non_existing_token';

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['accessToken' => $accessToken])
            ->willReturn(null)
        ;

        $result = $this->providerManager->findByAccessToken($accessToken);

        $this->assertNull($result);
    }

    public function testTimestampsAndBlameableAreSetOnCreateGoogle(): void
    {
        $providerName = ApiReference::GOOGLE;
        $user = (new User())->setEmail('test@gmail.com');
        $oauthUserData = new OAuthUserData(new GoogleUser(['sub' => '1234567890', 'email' => 'test@gmail.com']), 'access_token');

        $existingProvider = null;
        $expectedProvider = new Provider();

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $providerName, 'user' => $user])
            ->willReturn($existingProvider)
        ;

        $this->providerMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($oauthUserData, $providerName, $user, $existingProvider)
            ->willReturn($expectedProvider)
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('save')
            ->with($expectedProvider, true)
        ;

        $this->providerManager->createOrUpdateProvider($oauthUserData, $providerName, $user);

        $this->assertNotNull($expectedProvider->getCreatedAt());
        $this->assertSame('test@gmail.com', $expectedProvider->getCreatedBy());
    }

    public function testTimestampsAndBlameableAreSetOnCreateSpotify(): void
    {
        $providerName = ApiReference::SPOTIFY;
        $user = (new User())->setEmail('test@gmail.com');
        $oauthUserData = new OAuthUserData(new GoogleUser(['sub' => '1234567890', 'email' => 'test@gmail.com']), 'access_token');

        $existingProvider = null;
        $expectedProvider = new Provider();

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $providerName, 'user' => $user])
            ->willReturn($existingProvider)
        ;

        $this->providerMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($oauthUserData, $providerName, $user, $existingProvider)
            ->willReturn($expectedProvider)
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('save')
            ->with($expectedProvider, true)
        ;

        $this->providerManager->createOrUpdateProvider($oauthUserData, $providerName, $user);

        $this->assertNotNull($expectedProvider->getCreatedAt());
        $this->assertSame('test@gmail.com', $expectedProvider->getCreatedBy());
    }
}
