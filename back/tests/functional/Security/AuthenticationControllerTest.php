<?php

namespace App\Tests\Functional\Security;

use App\Entity\Provider;
use App\Provider\ProviderManager;
use App\Repository\UserRepository;
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
            './fixtures/authenticationController.yaml',
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
        $this->client->request('GET', '/auth/google');
        $this->assertResponseStatusCodeSame(302);
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString(self::GOOGLE_URL, $location);
    }

    public function testConnectSpotify(): void
    {
        $this->client->request('GET', '/auth/spotify');
        $this->assertResponseStatusCodeSame(302);
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString(self::SPOTIFY_URL, $location);
    }

    # CAN'T TEST CORRECTLY THE CALLBACK ROUTE BECAUSE CANT SIMULATE THE STATE PARAMETER
    public function testConnectCheckGoogle(): void
    {
        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn(null)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'google_access_token_123'));

        $this->client->request('GET', '/auth/google/callback');

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects();
        $this->assertResponseHeaderSame('Location', $_ENV['FRONTEND_URL']);
    }

    # CAN'T TEST CORRECTLY THE CALLBACK ROUTE BECAUSE CANT SIMULATE THE STATE PARAMETER
    public function testConnectCheckSpotify(): void
    {
        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn(null)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'spotify_access_token_123'));

        $this->client->request('GET', '/auth/spotify/callback');

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects();
        $this->assertResponseHeaderSame('Location', $_ENV['FRONTEND_URL']);
    }

    # CAN'T TEST CORRECTLY THE CALLBACK ROUTE BECAUSE CANT SIMULATE THE STATE PARAMETER
    public function testConnectCheckGoogleWithExistingUser(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john.doe@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'google_access_token_123'));

        $this->client->request('GET', '/auth/google/callback');

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects();
        $this->assertResponseHeaderSame('Location', $_ENV['FRONTEND_URL']);
    }

    # CAN'T TEST CORRECTLY THE CALLBACK ROUTE BECAUSE CANT SIMULATE THE STATE PARAMETER
    public function testConnectCheckSpotifyWithExistingUser(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'jane.smith@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'spotify_access_token_123'));

        $this->client->request('GET', '/auth/spotify/callback');

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

        $expectedJson = json_encode([
            'isAuthenticated' => true,
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'providers' => array_map(fn(Provider $p) => $p->getName(), $user->getProviders()->toArray()),
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, $this->client->getResponse()->getContent());
    }

    public function testGetUserProfileWithoutToken(): void
    {
        $this->client->request('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['isAuthenticated' => false, 'user' => null]),
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
        $this->assertJsonStringEqualsJsonString(
            json_encode(['isAuthenticated' => false, 'user' => null]),
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
        $this->assertJsonStringEqualsJsonString(
            json_encode(['isAuthenticated' => false, 'user' => null]),
            $this->client->getResponse()->getContent()
        );

        $cookieHeader = $this->client->getResponse()->headers->getCookies();
        $deletedCookie = array_filter($cookieHeader, fn($cookie) => 'AUTH_TOKEN' === $cookie->getName() && 0 !== $cookie->getExpiresTime());
        $this->assertNotEmpty($deletedCookie);
    }
}
