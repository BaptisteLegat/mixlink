<?php

namespace App\Tests\Functional\Security;

use App\Provider\ProviderManager;
use App\Repository\UserRepository;
use App\User\UserManager;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class AuthenticationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private static $loader;
    private UserRepository $userRepository;
    private ProviderManager|MockObject $providerManagerMock;

    private const GOOGLE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const SPOTIFY_URL = 'https://accounts.spotify.com/authorize';

    public static function setUpBeforeClass(): void
    {
        self::$loader = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        self::$loader->load([
            './fixtures/functionalTests/authenticationController.yaml',
        ]);
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);

        $this->providerManagerMock = $this->createMock(ProviderManager::class);
        static::getContainer()->set(ProviderManager::class, $this->providerManagerMock);
    }

    public function testConnectGoogle(): void
    {
        $this->client->request('GET', '/api/auth/google');
        $this->assertResponseStatusCodeSame(302);
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString(self::GOOGLE_URL, $location);
    }

    public function testConnectSpotify(): void
    {
        $this->client->request('GET', '/api/auth/spotify');
        $this->assertResponseStatusCodeSame(302);
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString(self::SPOTIFY_URL, $location);
    }

    // CAN'T TEST CORRECTLY THE CALLBACK ROUTE BECAUSE CANT SIMULATE THE STATE PARAMETER
    public function testConnectCheckGoogle(): void
    {
        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn(null)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'google_access_token_123'));

        $this->client->request('GET', '/api/auth/google/callback');

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects();
        $this->assertResponseHeaderSame('Location', $_ENV['FRONTEND_URL']);
    }

    // CAN'T TEST CORRECTLY THE CALLBACK ROUTE BECAUSE CANT SIMULATE THE STATE PARAMETER
    public function testConnectCheckSpotify(): void
    {
        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn(null)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'spotify_access_token_123'));

        $this->client->request('GET', '/api/auth/spotify/callback');

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects();
        $this->assertResponseHeaderSame('Location', $_ENV['FRONTEND_URL']);
    }

    // CAN'T TEST CORRECTLY THE CALLBACK ROUTE BECAUSE CANT SIMULATE THE STATE PARAMETER
    public function testConnectCheckGoogleWithExistingUser(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john.doe@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'google_access_token_123'));

        $this->client->request('GET', '/api/auth/google/callback');

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects();
        $this->assertResponseHeaderSame('Location', $_ENV['FRONTEND_URL']);
    }

    // CAN'T TEST CORRECTLY THE CALLBACK ROUTE BECAUSE CANT SIMULATE THE STATE PARAMETER
    public function testConnectCheckSpotifyWithExistingUser(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'jane.smith@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'spotify_access_token_123'));

        $this->client->request('GET', '/api/auth/spotify/callback');

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects();
        $this->assertResponseHeaderSame('Location', $_ENV['FRONTEND_URL']);
    }

    public function testGetUserProfileWithValidToken(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john.doe@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'spotify_access_token_123'));

        $this->client->request('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);

        $this->assertEquals($user->getId(), $responseData['id']);
        $this->assertEquals($user->getEmail(), $responseData['email']);
        $this->assertEquals('John', $responseData['firstName']);
        $this->assertEquals('Doe', $responseData['lastName']);
        $this->assertEquals('https://test.fr/profile1.jpg', $responseData['profilePicture']);

        $this->assertArrayHasKey('roles', $responseData);
        $this->assertContains('ROLE_USER', $responseData['roles']);

        $this->assertArrayHasKey('providers', $responseData);
        $this->assertIsArray($responseData['providers']);
        $this->assertCount(1, $responseData['providers']);

        $provider = $responseData['providers'][0];
        $this->assertArrayHasKey('id', $provider);
        $this->assertArrayHasKey('name', $provider);
        $this->assertEquals('google', $provider['name']);

        $this->assertArrayHasKey('subscription', $responseData);
        $this->assertArrayHasKey('id', $responseData['subscription']);
        $this->assertArrayHasKey('stripeSubscriptionId', $responseData['subscription']);
        $this->assertArrayHasKey('endDate', $responseData['subscription']);
        $this->assertArrayHasKey('startDate', $responseData['subscription']);
        $this->assertArrayHasKey('isActive', $responseData['subscription']);
        $this->assertArrayHasKey('plan', $responseData['subscription']);

        $plan = $responseData['subscription']['plan'];
        $this->assertArrayHasKey('id', $plan);
        $this->assertArrayHasKey('name', $plan);
        $this->assertEquals('premium', $plan['name']);
        $this->assertArrayHasKey('price', $plan);
        $this->assertEquals(3.99, $plan['price']);
        $this->assertArrayHasKey('currency', $plan);
        $this->assertEquals('EUR', $plan['currency']);
        $this->assertArrayHasKey('stripePriceId', $plan);
    }

    public function testGetUserProfileWithoutToken(): void
    {
        $this->client->request('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $this->assertEquals(
            '[]',
            $this->client->getResponse()->getContent()
        );
    }

    public function testGetUserProfileWithInvalidToken(): void
    {
        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn(null)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'invalid_access_token'));

        $this->client->request('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $this->assertEquals(
            '[]',
            $this->client->getResponse()->getContent()
        );
    }

    public function testLogout(): void
    {
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'fake_token'));
        $this->assertNotNull($this->client->getCookieJar()->get('AUTH_TOKEN'));

        $this->client->request('POST', '/api/logout');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $this->assertEquals(
            '[]',
            $this->client->getResponse()->getContent()
        );

        $cookieHeader = $this->client->getResponse()->headers->getCookies();
        $deletedCookie = array_filter($cookieHeader, fn ($cookie) => 'AUTH_TOKEN' === $cookie->getName() && 0 !== $cookie->getExpiresTime());
        $this->assertNotEmpty($deletedCookie);
    }

    public function testDeleteAccountWithoutToken(): void
    {
        $this->client->request('DELETE', '/api/me/delete');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteAccountWithInvalidToken(): void
    {
        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn(null)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'invalid_access_token'));

        $this->client->request('DELETE', '/api/me/delete');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteAccountWithException(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john.doe@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $userManagerMock = $this->createMock(UserManager::class);
        $userManagerMock->method('deleteUser')
            ->willThrowException(new Exception('Error deleting user'))
        ;

        static::getContainer()->set(UserManager::class, $userManagerMock);

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));

        $this->client->request('DELETE', '/api/me/delete');

        $this->assertResponseStatusCodeSame(500);

        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);

        $this->assertEquals('profile.delete_account.error', $responseData['error']);
    }

    public function testDeleteAccountWithValidToken(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john.doe@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));

        $this->client->request('DELETE', '/api/me/delete');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);

        $this->assertTrue($responseData['success']);

        $cookieHeader = $this->client->getResponse()->headers->getCookies();
        $deletedCookie = array_filter($cookieHeader, fn ($cookie) => 'AUTH_TOKEN' === $cookie->getName() && 0 !== $cookie->getExpiresTime());
        $this->assertNotEmpty($deletedCookie);
    }
}
