<?php

namespace App\Tests\Functional;

use App\Mercure\MercureManager;
use App\Provider\ProviderManager;
use App\Repository\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class MercureControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private static $loader;
    private UserRepository $userRepository;
    private ProviderManager|MockObject $providerManagerMock;
    private MercureManager|MockObject $mercureManagerMock;

    public static function setUpBeforeClass(): void
    {
        self::$loader = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        self::$loader->load([
            './fixtures/functionalTests/mercureController.yaml',
        ]);
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);

        $this->providerManagerMock = $this->createMock(ProviderManager::class);
        static::getContainer()->set(ProviderManager::class, $this->providerManagerMock);

        $this->mercureManagerMock = $this->createMock(MercureManager::class);
        static::getContainer()->set(MercureManager::class, $this->mercureManagerMock);
    }

    public function testGenerateTokenSuccess(): void
    {
        $this->mercureManagerMock
            ->method('generateTokenForSession')
            ->with('MERCURE1')
            ->willReturn([
                'token' => 'jwt_token',
                'mercureUrl' => 'http://localhost/.well-known/mercure',
            ])
        ;

        $this->client->request('GET', '/api/mercure/auth/MERCURE1');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('mercureUrl', $data);
    }

    public function testGenerateTokenSessionNotFound(): void
    {
        $this->client->request('GET', '/api/mercure/auth/UNKNOWN');
        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.create.error_session_not_found', $data['error']);
    }

    public function testGenerateTokenInternalError(): void
    {
        $this->mercureManagerMock
            ->method('generateTokenForSession')
            ->willThrowException(new RuntimeException('Erreur Mercure'))
        ;

        $this->client->request('GET', '/api/mercure/auth/MERCURE1');
        $this->assertResponseStatusCodeSame(500);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('common.error', $data['error']);
    }

    public function testGenerateHostTokenSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'mercure-host@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->mercureManagerMock
            ->method('generateTokenForHost')
            ->with('MERCURE1')
            ->willReturn([
                'token' => 'jwt_token_host',
                'mercureUrl' => 'http://localhost/.well-known/mercure',
            ])
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_token'));
        $this->client->request('GET', '/api/mercure/auth/host/MERCURE1');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('mercureUrl', $data);
    }

    public function testGenerateHostTokenNotHost(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'mercure-guest@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_token'));
        $this->client->request('GET', '/api/mercure/auth/host/MERCURE1');

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.create.error_not_host', $data['error']);
    }

    public function testGenerateHostTokenSessionNotFound(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'mercure-host@test.fr']);
        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_token'));
        $this->client->request('GET', '/api/mercure/auth/host/UNKNOWN');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.create.error_session_not_found', $data['error']);
    }

    public function testGenerateHostTokenInternalError(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'mercure-host@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->mercureManagerMock
            ->method('generateTokenForHost')
            ->willThrowException(new RuntimeException('Erreur Mercure Host'))
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_token'));
        $this->client->request('GET', '/api/mercure/auth/host/MERCURE1');

        $this->assertResponseStatusCodeSame(500);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('common.error', $data['error']);
    }

    public function testGenerateHostTokenUnauthenticated(): void
    {
        $this->client->request('GET', '/api/mercure/auth/host/MERCURE1');
        $this->assertResponseStatusCodeSame(401);
    }
}
