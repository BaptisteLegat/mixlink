<?php

namespace App\Tests\Functional;

use App\Provider\ProviderManager;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class ProviderControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private static $loader;
    private UserRepository $userRepository;

    public static function setUpBeforeClass(): void
    {
        self::bootKernel();
        self::$loader = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        self::$loader->load([
            './fixtures/functionalTests/providerController.yaml',
        ]);
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
    }

    public function testDisconnectProviderSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'alice.johnson@test.fr']);
        $provider = $user->getProviderByName('deezer');

        $this->client->getCookieJar()->set(new Cookie(
            'AUTH_TOKEN',
            $provider->getAccessToken()
        ));

        $this->client->request(
            'POST',
            '/api/provider/'.$provider->getId().'/disconnect'
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertTrue($response['mainProvider']);
    }

    public function testDisconnectSecondaryProvider(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john.doe@test.fr']);
        $mainProvider = $user->getProviderByName('google');
        $secondProvider = $user->getProviderByName('spotify_second');

        $this->client->getCookieJar()->set(new Cookie(
            'AUTH_TOKEN',
            $mainProvider->getAccessToken()
        ));

        $this->client->request(
            'POST',
            '/api/provider/'.$secondProvider->getId().'/disconnect'
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertFalse($response['mainProvider']);
    }

    public function testDisconnectProviderNotFound(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'jane.smith@test.fr']);

        $provider = $user->getProviders()->first();

        $this->client->getCookieJar()->set(new Cookie(
            'AUTH_TOKEN',
            $provider->getAccessToken()
        ));

        $this->client->request(
            'POST',
            '/api/provider/999/disconnect'
        );

        $this->assertResponseStatusCodeSame(404);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('provider.disconnect.error_provider_not_found', $response['error']);
    }

    public function testDisconnectProviderUnauthenticated(): void
    {
        $this->client->request(
            'POST',
            '/api/provider/123/disconnect'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDisconnectProviderException(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'jane.smith@test.fr']);
        $provider = $user->getProviderByName('spotify');

        $providerManagerMock = $this->createMock(ProviderManager::class);

        $providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $providerManagerMock
            ->method('disconnectProvider')
            ->willThrowException(new Exception('Test exception'))
        ;

        self::getContainer()->set(ProviderManager::class, $providerManagerMock);

        $this->client->getCookieJar()->set(new Cookie(
            'AUTH_TOKEN',
            $provider->getAccessToken()
        ));

        $this->client->request(
            'POST',
            '/api/provider/'.$provider->getId().'/disconnect'
        );

        $this->assertResponseStatusCodeSame(500);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('common.error', $response['error']);
    }
}
