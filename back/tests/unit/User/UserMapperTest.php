<?php

namespace App\Tests\Unit\User;

use App\ApiResource\ApiReference;
use App\Entity\Provider;
use App\Entity\Session;
use App\Entity\Subscription;
use App\Entity\User;
use App\Provider\ProviderMapper;
use App\Provider\ProviderModel;
use App\Security\Provider\SoundCloudUserData;
use App\Session\Mapper\SessionMapper;
use App\Session\Model\SessionModel;
use App\Subscription\SubscriptionMapper;
use App\Subscription\SubscriptionModel;
use App\User\UserMapper;
use App\User\UserModel;
use InvalidArgumentException;
use Kerox\OAuth2\Client\Provider\SpotifyResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserMapperTest extends TestCase
{
    private ProviderMapper|MockObject $providerMapper;
    private SubscriptionMapper|MockObject $subscriptionMapper;
    private SessionMapper|MockObject $sessionMapper;
    private UserMapper $userMapper;

    protected function setUp(): void
    {
        $this->providerMapper = $this->createMock(ProviderMapper::class);
        $this->subscriptionMapper = $this->createMock(SubscriptionMapper::class);
        $this->sessionMapper = $this->createMock(SessionMapper::class);
        $this->userMapper = new UserMapper(
            $this->providerMapper,
            $this->subscriptionMapper,
            $this->sessionMapper
        );
    }

    public function testMapEntityGoogleUser(): void
    {
        $googleData = [
            'sub' => '1234567890',
            'name' => 'John Doe',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'email' => 'test@gmail.com',
            'picture' => 'test',
            'locale' => 'en',
        ];
        $googleUser = new GoogleUser($googleData);

        $user = $this->userMapper->mapEntity($googleUser, ApiReference::GOOGLE, null);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('test@gmail.com', $user->getEmail());
        $this->assertEquals('test', $user->getProfilePicture());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testMapEntitySpotifyUser(): void
    {
        $spotifyUser = $this->createMock(SpotifyResourceOwner::class);
        $spotifyUser->method('getDisplayName')->willReturn('Spotify User');
        $spotifyUser->method('getEmail')->willReturn('spotify@test.com');
        $spotifyUser->method('getImages')->willReturn([
            ['url' => 'http://example.com/spotify_pic.jpg'],
        ]);

        $user = $this->userMapper->mapEntity($spotifyUser, ApiReference::SPOTIFY, null);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Spotify User', $user->getFirstName());
        $this->assertEquals('spotify@test.com', $user->getEmail());
        $this->assertEquals('http://example.com/spotify_pic.jpg', $user->getProfilePicture());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testMapEntityWithExistingUser(): void
    {
        $existingUser = new User();
        $existingUser->setFirstName('Old Name');
        $existingUser->setEmail('old@test.com');

        $googleData = [
            'sub' => '1234567890',
            'name' => 'John Doe',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'email' => 'test@gmail.com',
            'picture' => 'test',
            'locale' => 'en',
        ];
        $googleUser = new GoogleUser($googleData);

        $user = $this->userMapper->mapEntity($googleUser, ApiReference::GOOGLE, $existingUser);

        $this->assertSame($existingUser, $user);
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('test@gmail.com', $user->getEmail());
        $this->assertEquals('test', $user->getProfilePicture());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testMapEntityThrowsExceptionForUnsupportedProvider(): void
    {
        $googleData = [
            'sub' => '1234567890',
            'name' => 'John Doe',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'email' => 'test@gmail.com',
            'picture' => 'test',
            'locale' => 'en',
        ];
        $googleUser = new GoogleUser($googleData);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider unknown not supported');

        $this->userMapper->mapEntity($googleUser, 'unknown', null);
    }

    public function testMapEntitySoundCloudUser(): void
    {
        $soundcloudData = [
            'id' => 'sc_123',
            'first_name' => 'SCFirst',
            'last_name' => 'SCLast',
            'avatar_url' => 'http://soundcloud.com/avatar.jpg',
        ];
        $soundcloudUser = new SoundCloudUserData($soundcloudData);

        $user = $this->userMapper->mapEntity($soundcloudUser, ApiReference::SOUNDCLOUD, null);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('SCFirst', $user->getFirstName());
        $this->assertEquals('SCLast', $user->getLastName());
        $this->assertEquals('http://soundcloud.com/avatar.jpg', $user->getProfilePicture());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testMapEntitySoundCloudUserWithExistingUser(): void
    {
        $existingUser = new User();
        $existingUser->setFirstName('OldFirst')->setLastName('OldLast')->setProfilePicture('oldpic');
        $soundcloudData = [
            'id' => 'sc_456',
            'first_name' => 'NewFirst',
            'last_name' => 'NewLast',
            'avatar_url' => 'http://soundcloud.com/newavatar.jpg',
        ];
        $soundcloudUser = new SoundCloudUserData($soundcloudData);

        $user = $this->userMapper->mapEntity($soundcloudUser, ApiReference::SOUNDCLOUD, $existingUser);

        $this->assertSame($existingUser, $user);
        $this->assertEquals('NewFirst', $user->getFirstName());
        $this->assertEquals('NewLast', $user->getLastName());
        $this->assertEquals('http://soundcloud.com/newavatar.jpg', $user->getProfilePicture());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testMapModelWithEmptyUser(): void
    {
        $user = new User()
            ->setFirstName('')
            ->setLastName('')
            ->setEmail('')
            ->setProfilePicture('')
        ;

        $userModel = new UserModel();

        $this->sessionMapper->expects($this->never())
            ->method('mapModel')
        ;

        $result = $this->userMapper->mapModel($userModel, $user, null);
        $this->assertInstanceOf(UserModel::class, $result);
        $this->assertEquals('', $result->getFirstName());
        $this->assertEquals('', $result->getLastName());
        $this->assertEquals('', $result->getEmail());
        $this->assertEquals('', $result->getProfilePicture());
        $this->assertEquals(['ROLE_USER'], $result->getRoles());
        $this->assertNull($result->getCurrentSession());
    }

    public function testMapModelWithUserAndProviders(): void
    {
        $user = new User();
        $user->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('test@test.fr')
            ->setProfilePicture('test')
            ->setRoles(['ROLE_USER'])
            ->addProvider(new Provider())
            ->addProvider(new Provider())
        ;
        $userModel = new UserModel();
        $currentAccessToken = 'test_access_token';

        $this->providerMapper
            ->expects($this->exactly(2))
            ->method('mapModel')
            ->with($this->anything(), $currentAccessToken)
            ->willReturn(new ProviderModel())
        ;

        $this->subscriptionMapper
            ->expects($this->never())
            ->method('mapModel')
        ;

        $this->sessionMapper->expects($this->never())
            ->method('mapModel')
        ;

        $result = $this->userMapper->mapModel($userModel, $user, $currentAccessToken);
        $this->assertInstanceOf(UserModel::class, $result);
        $this->assertEquals('John', $result->getFirstName());
        $this->assertEquals('Doe', $result->getLastName());
        $this->assertEquals('test@test.fr', $result->getEmail());
        $this->assertEquals('test', $result->getProfilePicture());
        $this->assertEquals(['ROLE_USER'], $result->getRoles());
        $this->assertCount(2, $result->getProviders());
        $this->assertNull($result->getSubscription());
        $this->assertNull($result->getCurrentSession());
    }

    public function testMapModelWithUserAndSubscription(): void
    {
        $user = new User();
        $user->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('test@test.fr')
            ->setProfilePicture('test')
            ->setRoles(['ROLE_USER'])
            ->setSubscription(new Subscription())
        ;

        $userModel = new UserModel();
        $this->providerMapper
            ->expects($this->never())
            ->method('mapModel')
        ;

        $this->subscriptionMapper
            ->expects($this->once())
            ->method('mapModel')
            ->willReturn(new SubscriptionModel())
        ;

        $this->sessionMapper->expects($this->never())
            ->method('mapModel')
        ;

        $result = $this->userMapper->mapModel($userModel, $user, null);
        $this->assertInstanceOf(UserModel::class, $result);
        $this->assertEquals('John', $result->getFirstName());
        $this->assertEquals('Doe', $result->getLastName());
        $this->assertEquals('test@test.fr', $result->getEmail());
        $this->assertEquals('test', $result->getProfilePicture());
        $this->assertEquals(['ROLE_USER'], $result->getRoles());
        $this->assertCount(0, $result->getProviders());
        $this->assertInstanceOf(SubscriptionModel::class, $result->getSubscription());
        $this->assertEquals($user->getSubscription()->getId(), $result->getSubscription()->getId());
        $this->assertNull($result->getCurrentSession());
    }

    public function testMapModelWithCurrentAccessToken(): void
    {
        $user = new User();
        $user->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('test@test.fr')
            ->setProfilePicture('test')
            ->setRoles(['ROLE_USER'])
            ->addProvider(new Provider())
        ;

        $userModel = new UserModel();
        $currentAccessToken = 'access_token_12345';

        $this->providerMapper
            ->expects($this->once())
            ->method('mapModel')
            ->with($this->anything(), $currentAccessToken)
            ->willReturn(new ProviderModel())
        ;

        $this->sessionMapper->expects($this->never())
            ->method('mapModel')
        ;

        $result = $this->userMapper->mapModel($userModel, $user, $currentAccessToken);
        $this->assertInstanceOf(UserModel::class, $result);
        $this->assertCount(1, $result->getProviders());
        $this->assertNull($result->getCurrentSession());
    }

    public function testMapModelWithCurrentSession(): void
    {
        $user = new User();
        $user->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('test@test.fr')
            ->setProfilePicture('test')
            ->setRoles(['ROLE_USER'])
        ;

        $session = new Session()
            ->setEndedAt(null)
            ->setHost($user)
        ;

        $user->addSession($session);

        $this->sessionMapper
            ->expects($this->once())
            ->method('mapModel')
            ->with($session)
            ->willReturn(new SessionModel())
        ;

        $userModel = new UserModel();

        $result = $this->userMapper->mapModel($userModel, $user, null);
        $this->assertInstanceOf(UserModel::class, $result);
        $this->assertNotNull($result->getCurrentSession());
        $this->assertInstanceOf(SessionModel::class, $result->getCurrentSession());
    }
}
