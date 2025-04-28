<?php

namespace App\Tests\Unit\User;

use App\ApiResource\ApiReference;
use App\Entity\User;
use App\Provider\ProviderManager;
use App\Repository\UserRepository;
use App\Security\OAuthUserData;
use App\User\UserManager;
use App\User\UserMapper;
use App\User\UserModel;
use League\OAuth2\Client\Provider\GoogleUser;
use Monolog\Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class UserManagerTest extends TestCase
{
    private UserMapper|MockObject $userMapperMocked;
    private ProviderManager|MockObject $providerManagerMocked;
    private UserRepository|MockObject $userRepoMocked;
    private UserManager $userManager;

    protected function setUp(): void
    {
        $this->userMapperMocked = $this->createMock(UserMapper::class);
        $this->providerManagerMocked = $this->createMock(ProviderManager::class);
        $this->userRepoMocked = $this->createMock(UserRepository::class);

        $this->userManager = new UserManager(
            $this->userMapperMocked,
            $this->providerManagerMocked,
            $this->userRepoMocked
        );
    }

    public function testCreateUser(): void
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

        $this->userRepoMocked
            ->expects($this->once())
            ->method('findOneBy')
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

    public function testUpdateUser(): void
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

        $this->userRepoMocked
            ->expects($this->once())
            ->method('findOneBy')
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
}
