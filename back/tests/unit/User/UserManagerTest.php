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
use Kerox\OAuth2\Client\Provider\SpotifyResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use Monolog\Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

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
            ->with($userModel, $user)
            ->willReturn($userModel)
        ;

        $result = $this->userManager->getUserModel($user);

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
}
