<?php

namespace App\Tests\Unit\User;

use App\ApiResource\ApiReference;
use App\Entity\Provider;
use App\Entity\Subscription;
use App\Entity\User;
use App\Provider\ProviderManager;
use App\Repository\UserRepository;
use App\Security\OAuthUserData;
use App\Subscription\SubscriptionManager;
use App\User\UserManager;
use App\User\UserMapper;
use App\User\UserModel;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use Exception;
use InvalidArgumentException;
use Kerox\OAuth2\Client\Provider\SpotifyResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use Martin1982\OAuth2\Client\Provider\SoundCloudResourceOwner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Uid\Uuid;

class UserManagerTest extends TestCase
{
    private UserMapper|MockObject $userMapperMocked;
    private ProviderManager|MockObject $providerManagerMocked;
    private UserRepository|MockObject $userRepoMocked;
    private LoggerInterface|MockObject $loggerMocked;
    private EntityManagerInterface|MockObject $entityManagerMocked;
    private SubscriptionManager|MockObject $subscriptionManagerMocked;
    private UserManager $userManager;
    private FilterCollection|MockObject $filterCollectionMock;

    protected function setUp(): void
    {
        $this->userMapperMocked = $this->createMock(UserMapper::class);
        $this->providerManagerMocked = $this->createMock(ProviderManager::class);
        $this->userRepoMocked = $this->createMock(UserRepository::class);
        $this->loggerMocked = $this->createMock(LoggerInterface::class);
        $this->entityManagerMocked = $this->createMock(EntityManagerInterface::class);
        $this->subscriptionManagerMocked = $this->createMock(SubscriptionManager::class);
        $this->filterCollectionMock = $this->createMock(FilterCollection::class);

        $this->entityManagerMocked->method('getFilters')
            ->willReturn($this->filterCollectionMock);

        $this->userManager = new UserManager(
            $this->userMapperMocked,
            $this->providerManagerMocked,
            $this->userRepoMocked,
            $this->loggerMocked,
            $this->entityManagerMocked,
            $this->subscriptionManagerMocked
        );
    }

    public function testCreateNewUser(): void
    {
        $user = new User();
        $google = new GoogleUser([
            'sub' => '1234567890',
            'name' => 'John Doe',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'email' => 'test@gmail.com',
            'picture' => '',
            'locale' => 'en',
        ]);

        $oAuthUserData = new OAuthUserData($google, 'access_token');

        $this->filterCollectionMock->expects($this->once())
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->userRepoMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@gmail.com'])
            ->willReturn(null)
        ;

        $this->userMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($google, ApiReference::GOOGLE, null)
            ->willReturn($user)
        ;

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::GOOGLE, $user)
        ;

        $this->entityManagerMocked
            ->expects($this->once())
            ->method('refresh')
            ->with($user)
        ;

        $this->userRepoMocked
            ->expects($this->once())
            ->method('save')
            ->with($user, true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::GOOGLE);

        $this->assertSame($user, $result);
    }

    public function testUpdateExistingUser(): void
    {
        $user = new User();
        $google = new GoogleUser([
            'sub' => '1234567890',
            'name' => 'John Doe',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'email' => 'test@gmail.com',
            'picture' => '',
            'locale' => 'en',
        ]);

        $oAuthUserData = new OAuthUserData($google, 'access_token');

        $this->filterCollectionMock->expects($this->once())
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->userRepoMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@gmail.com'])
            ->willReturn($user)
        ;

        $this->userMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($google, ApiReference::GOOGLE, $user)
            ->willReturn($user)
        ;

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::GOOGLE, $user)
        ;

        $this->entityManagerMocked
            ->expects($this->once())
            ->method('refresh')
            ->with($user)
        ;

        $this->userRepoMocked
            ->expects($this->once())
            ->method('save')
            ->with($user, true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::GOOGLE);

        $this->assertSame($user, $result);
    }

    public function testReactivateDeletedUser(): void
    {
        $provider = new Provider()
            ->setName(ApiReference::GOOGLE)
            ->setDeletedAt(new DateTime())
        ;
        $user = new User()
            ->setDeletedAt(new DateTime())
            ->addProvider($provider)
        ;

        $google = new GoogleUser([
            'sub' => '1234567890',
            'name' => 'John Doe',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'email' => 'test@gmail.com',
            'picture' => '',
            'locale' => 'en',
        ]);

        $oAuthUserData = new OAuthUserData($google, 'access_token');

        $this->filterCollectionMock->expects($this->exactly(2))
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock->expects($this->exactly(2))
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->userRepoMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@gmail.com'])
            ->willReturn($user)
        ;

        $this->userMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($google, ApiReference::GOOGLE, $user)
            ->willReturn($user)
        ;

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::GOOGLE, $user)
        ;

        $this->entityManagerMocked
            ->expects($this->once())
            ->method('refresh')
            ->with($user)
        ;

        $this->userRepoMocked
            ->expects($this->once())
            ->method('save')
            ->with($user, true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::GOOGLE);

        $this->assertSame($user, $result);
        $this->assertNull($user->getDeletedAt());
        foreach ($user->getProviders() as $p) {
            if (ApiReference::GOOGLE === $p->getName()) {
                $this->assertNull($p->getDeletedAt());
            }
        }
    }

    public function testGetUserModel(): void
    {
        $user = new User();
        $userModel = new UserModel();

        $this->userMapperMocked
            ->expects($this->once())
            ->method('mapModel')
            ->with($this->isInstanceOf(UserModel::class), $user, null)
            ->willReturn($userModel)
        ;

        $result = $this->userManager->getUserModel($user);

        $this->assertSame($userModel, $result);
    }

    public function testGetUserModelWithAccessToken(): void
    {
        $user = new User();
        $userModel = new UserModel();
        $currentAccessToken = 'access_token_test_123';

        $this->userMapperMocked
            ->expects($this->once())
            ->method('mapModel')
            ->with(
                $this->isInstanceOf(UserModel::class),
                $user,
                $currentAccessToken
            )
            ->willReturn($userModel)
        ;

        $result = $this->userManager->getUserModel($user, $currentAccessToken);

        $this->assertSame($userModel, $result);
    }

    public function testDeleteUserSuccessfully(): void
    {
        $subscription = new Subscription()->setStripeSubscriptionId('sub_123');
        $user = new User()->setSubscription($subscription);

        $this->subscriptionManagerMocked
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with($user)
        ;

        $this->userRepoMocked
            ->expects($this->once())
            ->method('remove')
            ->with($user, true)
        ;

        $this->loggerMocked
            ->expects($this->never())
            ->method('error')
        ;

        $this->userManager->deleteUser($user);
    }

    public function testDeleteUserWithSubscriptionError(): void
    {
        $subscription = new Subscription()->setStripeSubscriptionId('sub_123');
        $user = new User()->setSubscription($subscription);

        $exception = new Exception('Subscription error');

        $this->subscriptionManagerMocked
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with($user)
            ->willThrowException($exception)
        ;

        $this->loggerMocked
            ->expects($this->once())
            ->method('error')
            ->with('Failed to cancel subscription before user deletion', [
                'userId' => $user->getId(),
                'subscriptionId' => 'sub_123',
                'message' => 'Subscription error',
            ]);

        $this->userRepoMocked
            ->expects($this->once())
            ->method('remove')
            ->with($user, true)
        ;

        $this->userManager->deleteUser($user);
    }

    public function testDeleteUserWithRepositoryError(): void
    {
        $user = new User();
        $exception = new Exception('Repository error');

        $this->userRepoMocked
            ->expects($this->once())
            ->method('remove')
            ->with($user, true)
            ->willThrowException($exception)
        ;

        $this->loggerMocked
            ->expects($this->once())
            ->method('error')
            ->with('Failed to delete user', [
                'userId' => $user->getId(),
                'message' => 'Repository error',
                'trace' => $exception->getTraceAsString(),
            ])
        ;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Repository error');

        $this->userManager->deleteUser($user);
    }

    public function testCreateWithSpotifyProvider(): void
    {
        $user = new User();
        $spotify = new SpotifyResourceOwner([
            'id' => 'spotify123',
            'display_name' => 'Spotify User',
            'email' => 'spotify@test.com',
        ]);

        $oAuthUserData = new OAuthUserData($spotify, 'access_token');

        $this->filterCollectionMock->expects($this->once())
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->userRepoMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'spotify@test.com'])
            ->willReturn(null)
        ;

        $this->userMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($spotify, ApiReference::SPOTIFY, null)
            ->willReturn($user)
        ;

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::SPOTIFY, $user);

        $this->userRepoMocked
            ->expects($this->once())
            ->method('save')
            ->with($user, true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::SPOTIFY);

        $this->assertSame($user, $result);
    }

    public function testUpdateEmailForSoundCloudUserSuccess(): void
    {
        $user = new User();
        $provider = new Provider()->setName(ApiReference::SOUNDCLOUD);
        $user->addProvider($provider);
        $provider->setUser($user);

        $this->userRepoMocked->expects($this->once())
            ->method('save')
            ->with($user, true)
        ;

        $this->userManager->updateEmailForSoundCloudUser($user, 'new@email.com');

        $this->assertSame('new@email.com', $user->getEmail());
        $this->assertSame('new@email.com', $provider->getCreatedBy());
        $this->assertSame('new@email.com', $provider->getUpdatedBy());
    }

    public function testUpdateEmailForSoundCloudUserThrowsIfNotSoundCloud(): void
    {
        $user = new User();
        $provider = new Provider()->setName(ApiReference::GOOGLE);
        $user->addProvider($provider);
        $provider->setUser($user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The user has not only a SoundCloud provider');

        $this->userManager->updateEmailForSoundCloudUser($user, 'test@email.com');
    }

    public function testUpdateEmailForSoundCloudUserThrowsIfMultipleProviders(): void
    {
        $user = new User();
        $provider1 = new Provider()->setName(ApiReference::SOUNDCLOUD);
        $provider2 = new Provider()->setName(ApiReference::SOUNDCLOUD);
        $user->addProvider($provider1);
        $user->addProvider($provider2);
        $provider1->setUser($user);
        $provider2->setUser($user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The user has not only a SoundCloud provider');

        $this->userManager->updateEmailForSoundCloudUser($user, 'test@email.com');
    }

    public function testCreateSoundCloudUserProviderNotFound(): void
    {
        $soundcloud = $this->createMock(SoundCloudResourceOwner::class);
        $soundcloud->method('getId')->willReturn('sc_123');
        $oAuthUserData = new OAuthUserData($soundcloud, 'access_token');

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('findByProviderUserId')
            ->with(ApiReference::SOUNDCLOUD, 'sc_123')
            ->willReturn(null)
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->userMapperMocked->expects($this->once())
            ->method('mapEntity')
            ->with($soundcloud, ApiReference::SOUNDCLOUD, null)
            ->willReturn(new User())
        ;

        $this->providerManagerMocked->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::SOUNDCLOUD, $this->isInstanceOf(User::class))
        ;

        $this->entityManagerMocked->expects($this->once())
            ->method('refresh')
            ->with($this->isInstanceOf(User::class))
        ;

        $this->userRepoMocked->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class), true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::SOUNDCLOUD);
        $this->assertInstanceOf(User::class, $result);
    }

    public function testCreateSoundCloudUserProviderFoundButUserNull(): void
    {
        $soundcloud = $this->createMock(SoundCloudResourceOwner::class);
        $soundcloud->method('getId')->willReturn('sc_456');
        $oAuthUserData = new OAuthUserData($soundcloud, 'access_token');

        $providerMock = $this->createMock(Provider::class);
        $providerMock->method('getUser')->willReturn(null);

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('findByProviderUserId')
            ->with(ApiReference::SOUNDCLOUD, 'sc_456')
            ->willReturn($providerMock)
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->userMapperMocked->expects($this->once())
            ->method('mapEntity')
            ->with($soundcloud, ApiReference::SOUNDCLOUD, null)
            ->willReturn(new User())
        ;

        $this->providerManagerMocked->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::SOUNDCLOUD, $this->isInstanceOf(User::class))
        ;

        $this->entityManagerMocked->expects($this->once())
            ->method('refresh')
            ->with($this->isInstanceOf(User::class))
        ;

        $this->userRepoMocked->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class), true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::SOUNDCLOUD);
        $this->assertInstanceOf(User::class, $result);
    }

    public function testCreateSoundCloudUserProviderSoftDeletedEmailNullThrows(): void
    {
        $soundcloud = $this->createMock(SoundCloudResourceOwner::class);
        $soundcloud->method('getId')->willReturn('sc_789');
        $oAuthUserData = new OAuthUserData($soundcloud, 'access_token');

        $providerMock = $this->createMock(Provider::class);
        $providerMock->method('getUser')->willReturn(new User());
        $providerMock->method('getDeletedAt')->willReturn(new DateTime());
        $providerMock->expects($this->once())->method('setDeletedAt')->with(null);

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('findByProviderUserId')
            ->with(ApiReference::SOUNDCLOUD, 'sc_789')
            ->willReturn($providerMock)
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is required for SoundCloud user');

        $this->userManager->create($oAuthUserData, ApiReference::SOUNDCLOUD);
    }

    public function testCreateSoundCloudUserProviderSoftDeletedEmailValid(): void
    {
        $soundcloud = $this->createMock(SoundCloudResourceOwner::class);
        $soundcloud->method('getId')->willReturn('sc_999');
        $oAuthUserData = new OAuthUserData($soundcloud, 'access_token');

        $user = new User()->setEmail('soundcloud@test.com');
        $providerMock = $this->createMock(Provider::class);
        $providerMock->method('getUser')->willReturn($user);
        $providerMock->method('getDeletedAt')->willReturn(new DateTime());
        $providerMock->expects($this->once())->method('setDeletedAt')->with(null);

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('findByProviderUserId')
            ->with(ApiReference::SOUNDCLOUD, 'sc_999')
            ->willReturn($providerMock)
        ;

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('saveProvider')
            ->with($providerMock, 'soundcloud@test.com', true)
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->userMapperMocked->expects($this->once())
            ->method('mapEntity')
            ->with($soundcloud, ApiReference::SOUNDCLOUD, $user)
            ->willReturn($user)
        ;

        $this->providerManagerMocked->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::SOUNDCLOUD, $user)
        ;

        $this->entityManagerMocked->expects($this->once())
            ->method('refresh')
            ->with($user)
        ;

        $this->userRepoMocked->expects($this->once())
            ->method('save')
            ->with($user, true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::SOUNDCLOUD);
        $this->assertSame($user, $result);
    }

    public function testCreateWithEmptyEmail(): void
    {
        $google = $this->createMock(GoogleUser::class);
        $google->method('getEmail')->willReturn('');
        $oAuthUserData = new OAuthUserData($google, 'access_token');

        $this->userMapperMocked->expects($this->once())
            ->method('mapEntity')
            ->with($google, ApiReference::GOOGLE, null)
            ->willReturn(new User())
        ;

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::GOOGLE, $this->isInstanceOf(User::class))
        ;

        $this->entityManagerMocked->expects($this->once())
            ->method('refresh')
            ->with($this->isInstanceOf(User::class))
        ;

        $this->userRepoMocked->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class), true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::GOOGLE);
        $this->assertInstanceOf(User::class, $result);
    }

    public function testUpdateEmailForSoundCloudUserWithActiveProviderNoReactivation(): void
    {
        $user = new User();
        $provider = $this->createMock(Provider::class);
        $provider->method('getName')->willReturn(ApiReference::SOUNDCLOUD);
        $provider->method('getDeletedAt')->willReturn(null);
        $provider->expects($this->never())->method('setDeletedAt');
        $provider->method('setCreatedBy')->willReturn($provider);
        $provider->method('setUpdatedBy')->willReturn($provider);
        $user->addProvider($provider);
        $provider->setUser($user);

        $this->userRepoMocked->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'new@email.com'])
            ->willReturn(null);

        $this->userRepoMocked->expects($this->once())
            ->method('save')
            ->with($user, true)
        ;

        $this->userManager->updateEmailForSoundCloudUser($user, 'new@email.com');
        $this->assertSame('new@email.com', $user->getEmail());
    }

    public function testCreateReactivatesSoftDeletedProvider(): void
    {
        $soundcloud = $this->createMock(SoundCloudResourceOwner::class);
        $soundcloud->method('getId')->willReturn('sc_softdel');
        $oAuthUserData = new OAuthUserData($soundcloud, 'access_token');

        $user = new User();
        $user->setEmail('soundcloud@reactive.com');
        $providerMock = $this->createMock(Provider::class);
        $providerMock->method('getUser')->willReturn($user);
        $providerMock->method('getDeletedAt')->willReturn(new DateTime());
        $providerMock->expects($this->once())
            ->method('setDeletedAt')
            ->with(null)
        ;

        $this->providerManagerMocked->expects($this->once())
            ->method('findByProviderUserId')
            ->with(ApiReference::SOUNDCLOUD, 'sc_softdel')
            ->willReturn($providerMock)
        ;

        $this->providerManagerMocked->expects($this->once())
            ->method('saveProvider')
            ->with($providerMock, 'soundcloud@reactive.com', true)
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->userMapperMocked->expects($this->once())
            ->method('mapEntity')
            ->with($soundcloud, ApiReference::SOUNDCLOUD, $user)
            ->willReturn($user)
        ;

        $this->providerManagerMocked->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::SOUNDCLOUD, $user)
        ;

        $this->entityManagerMocked->expects($this->once())
            ->method('refresh')
            ->with($user)
        ;

        $this->userRepoMocked->expects($this->once())
            ->method('save')
            ->with($user, true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::SOUNDCLOUD);
        $this->assertInstanceOf(User::class, $result);
    }

    public function testCreateSoundCloudUserProviderNotSoftDeleted(): void
    {
        $soundcloud = $this->createMock(SoundCloudResourceOwner::class);
        $soundcloud->method('getId')->willReturn('sc_321');
        $oAuthUserData = new OAuthUserData($soundcloud, 'access_token');

        $user = new User();
        $providerMock = $this->createMock(Provider::class);
        $providerMock->method('getUser')->willReturn($user);
        $providerMock->method('getDeletedAt')->willReturn(null);
        $providerMock->expects($this->never())->method('setDeletedAt');
        $this->providerManagerMocked->expects($this->never())->method('saveProvider');

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('findByProviderUserId')
            ->with(ApiReference::SOUNDCLOUD, 'sc_321')
            ->willReturn($providerMock)
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock->expects($this->once())
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->userMapperMocked->expects($this->once())
            ->method('mapEntity')
            ->with($soundcloud, ApiReference::SOUNDCLOUD, $user)
            ->willReturn($user)
        ;

        $this->providerManagerMocked->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::SOUNDCLOUD, $user)
        ;

        $this->entityManagerMocked->expects($this->once())
            ->method('refresh')
            ->with($user)
        ;

        $this->userRepoMocked->expects($this->once())
            ->method('save')
            ->with($user, true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::SOUNDCLOUD);
        $this->assertInstanceOf(User::class, $result);
    }

    public function testUpdateEmailForSoundCloudUserWithExistingUser(): void
    {
        $user = new User();
        $user->setEmail('old@email.com');
        $provider = new Provider();
        $provider->setName(ApiReference::SOUNDCLOUD);
        $user->addProvider($provider);
        $provider->setUser($user);

        $existingUser = new User();
        $existingUser->setEmail('new@email.com');
        $existingUserId = Uuid::v4();
        $reflection = new ReflectionClass($existingUser);
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($existingUser, $existingUserId);

        $userId = Uuid::v4();
        $reflectionUser = new ReflectionClass($user);
        $idPropUser = $reflectionUser->getProperty('id');
        $idPropUser->setAccessible(true);
        $idPropUser->setValue($user, $userId);

        $this->userRepoMocked->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'new@email.com'])
            ->willReturn($existingUser)
        ;

        $this->providerManagerMocked->expects($this->once())
            ->method('saveProvider')
            ->with($provider, 'new@email.com', true)
        ;

        $this->userRepoMocked->expects($this->once())
            ->method('hardDelete')
            ->with($user)
        ;

        $this->userManager->updateEmailForSoundCloudUser($user, 'new@email.com');
        $this->assertSame($existingUser, $provider->getUser());
    }

    public function testReactivateDeletedUserWithDifferentProviderName(): void
    {
        $provider = new Provider()
            ->setName(ApiReference::SPOTIFY)
            ->setDeletedAt(new DateTime())
        ;

        $user = new User()
            ->setDeletedAt(new DateTime())
            ->addProvider($provider)
        ;

        $google = new GoogleUser([
            'sub' => '1234567890',
            'name' => 'John Doe',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'email' => 'test@gmail.com',
            'picture' => '',
            'locale' => 'en',
        ]);

        $oAuthUserData = new OAuthUserData($google, 'access_token');

        $this->filterCollectionMock->expects($this->exactly(2))
            ->method('disable')
            ->with('softdeleteable')
        ;

        $this->filterCollectionMock->expects($this->exactly(2))
            ->method('enable')
            ->with('softdeleteable')
        ;

        $this->userRepoMocked
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@gmail.com'])
            ->willReturn($user)
        ;

        $this->userMapperMocked
            ->expects($this->once())
            ->method('mapEntity')
            ->with($google, ApiReference::GOOGLE, $user)
            ->willReturn($user)
        ;

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('createOrUpdateProvider')
            ->with($oAuthUserData, ApiReference::GOOGLE, $user)
        ;

        $this->entityManagerMocked
            ->expects($this->once())
            ->method('refresh')
            ->with($user)
        ;

        $this->userRepoMocked
            ->expects($this->once())
            ->method('save')
            ->with($user, true)
        ;

        $result = $this->userManager->create($oAuthUserData, ApiReference::GOOGLE);

        $this->assertSame($user, $result);
        $this->assertNull($user->getDeletedAt());

        foreach ($user->getProviders() as $p) {
            if (ApiReference::SPOTIFY === $p->getName()) {
                $this->assertNotNull($p->getDeletedAt());
            }
        }
    }
}
