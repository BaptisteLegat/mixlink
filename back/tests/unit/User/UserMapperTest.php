<?php

namespace App\Tests\Unit\User;

use App\ApiResource\ApiReference;
use App\Entity\User;
use App\User\UserMapper;
use InvalidArgumentException;
use League\OAuth2\Client\Provider\GoogleUser;
use Kerox\OAuth2\Client\Provider\SpotifyResourceOwner;
use PHPUnit\Framework\TestCase;

class UserMapperTest extends TestCase
{
    private UserMapper $userMapper;

    protected function setUp(): void
    {
        $this->userMapper = new UserMapper();
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
            ['url' => 'http://example.com/spotify_pic.jpg']
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
        $this->expectExceptionMessage("Provider unknown not supported");

        $this->userMapper->mapEntity($googleUser, 'unknown', null);
    }
}
