<?php

namespace App\Tests\Unit\Provider;

use App\ApiResource\ApiReference;
use App\Entity\Provider;
use App\Entity\User;
use App\Provider\ProviderManager;
use App\Provider\ProviderMapper;
use App\Repository\ProviderRepository;
use App\Security\OAuthUserData;
use App\Tests\Unit\PHPUnitHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use Exception;
use League\OAuth2\Client\Provider\GoogleUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProviderManagerTest extends TestCase
{
    use PHPUnitHelper;

    private ProviderRepository|MockObject $providerRepositoryMocked;
    private ProviderMapper|MockObject $providerMapperMocked;
    private EntityManagerInterface|MockObject $entityManagerMocked;
    private LoggerInterface|MockObject $loggerMocked;
    private FilterCollection|MockObject $filterCollectionMock;
    private ProviderManager $providerManager;

    protected function setUp(): void
    {
        $this->providerRepositoryMocked = $this->createMock(ProviderRepository::class);
        $this->providerMapperMocked = $this->createMock(ProviderMapper::class);
        $this->entityManagerMocked = $this->createMock(EntityManagerInterface::class);
        $this->loggerMocked = $this->createMock(LoggerInterface::class);
        $this->filterCollectionMock = $this->createMock(FilterCollection::class);

        $this->entityManagerMocked->method('getFilters')
            ->willReturn($this->filterCollectionMock);

        $this->providerManager = new ProviderManager(
            $this->providerRepositoryMocked,
            $this->providerMapperMocked,
            $this->entityManagerMocked,
            $this->loggerMocked
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

    public function testCreateOrUpdateProviderWithDeletedProvider(): void
    {
        $providerName = ApiReference::GOOGLE;
        $user = new User()->setEmail('test@mixlink.fr');
        $oauthUserData = new OAuthUserData(new GoogleUser(['sub' => '1234567890', 'email' => 'test@mixlink.fr']), 'access_token');
        $existingProvider = new Provider();

        $existingProvider->setDeletedAt(new DateTime())
            ->setName($providerName)
            ->setUser($user)
            ->setAccessToken('old_access_token')
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $providerName, 'user' => $user])
            ->willReturn($existingProvider)
        ;

        $this->filterCollectionMock
            ->expects($this->once())
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock
            ->expects($this->once())
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->providerMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($oauthUserData, $providerName, $user, $existingProvider)
            ->willReturn($existingProvider)
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('save')
            ->with($existingProvider, true)
        ;

        $this->providerManager->createOrUpdateProvider($oauthUserData, $providerName, $user);
        $this->assertNull($existingProvider->getDeletedAt());
    }

    public function testDisconnectProviderSuccess(): void
    {
        $providerId = '123';
        $user = new User();
        $accessToken = 'valid_token';

        $provider = new Provider()
            ->setUser($user)
            ->setAccessToken($accessToken)
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('find')
            ->with($providerId)
            ->willReturn($provider)
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['accessToken' => $accessToken])
            ->willReturn($provider)
        ;

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('remove')
            ->with($provider, true)
        ;

        $result = $this->providerManager->disconnectProvider($providerId, $user);

        $this->assertTrue($result);
    }

    public function testDisconnectProviderNotFound(): void
    {
        $providerId = '123';
        $user = new User();

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('find')
            ->with($providerId)
            ->willReturn(null)
        ;

        $result = $this->providerManager->disconnectProvider($providerId, $user);

        $this->assertNull($result);
    }

    public function testDisconnectProviderUserNotFound(): void
    {
        $providerId = '123';
        $user = new User();

        $provider = new Provider();
        $provider->setUser(null);

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('find')
            ->with($providerId)
            ->willReturn($provider)
        ;

        $result = $this->providerManager->disconnectProvider($providerId, $user);

        $this->assertNull($result);
    }

    public function testDisconnectProviderThrowsException(): void
    {
        $providerId = '123';
        $user = new User();
        $exceptionMessage = 'Database error';

        $provider = new Provider();
        $provider->setUser($user);
        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('find')
            ->with($providerId)
            ->willThrowException(new Exception($exceptionMessage))
        ;

        $this->loggerMocked
            ->expects($this->once())
            ->method('error')
            ->with('Error disconnecting provider', [
                'providerId' => $providerId,
                'userId' => $user->getId(),
                'error' => $exceptionMessage,
            ])
        ;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->providerManager->disconnectProvider($providerId, $user);
    }

    public function testIsProviderCurrentlyUsed(): void
    {
        $provider = new Provider();
        $provider->setAccessToken('valid_token');

        $user = new User();

        $provider->setUser($user);

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['accessToken' => 'valid_token'])
            ->willReturn($provider)
        ;

        $result = $this->providerManager->isProviderCurrentlyUsed($provider);

        $this->assertTrue($result);
    }

    public function testIsProviderCurrentlyUsedReturnsFalseWhenNoAccessToken(): void
    {
        $provider = new Provider();
        $provider->setAccessToken(null);

        $result = $this->providerManager->isProviderCurrentlyUsed($provider);

        $this->assertFalse($result);
    }

    public function testIsProviderCurrentlyUsedReturnsFalseWhenUserNotFound(): void
    {
        $provider = new Provider();
        $provider->setAccessToken('valid_token');

        $this->providerRepositoryMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['accessToken' => 'valid_token'])
            ->willReturn(null)
        ;

        $result = $this->providerManager->isProviderCurrentlyUsed($provider);

        $this->assertFalse($result);
    }
}
