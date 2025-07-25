<?php

namespace App\Tests\Functional;

use App\Provider\ProviderManager;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use App\Session\Manager\SessionManager;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class SessionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private static $loader;
    private UserRepository $userRepository;
    private SessionRepository $sessionRepository;
    private ProviderManager|MockObject $providerManagerMock;

    public static function setUpBeforeClass(): void
    {
        self::$loader = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        self::$loader->load([
            './fixtures/functionalTests/sessionController.yaml',
        ]);
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->sessionRepository = static::getContainer()->get(SessionRepository::class);
        $this->providerManagerMock = $this->createMock(ProviderManager::class);
        static::getContainer()->set(ProviderManager::class, $this->providerManagerMock);
    }

    public function testGetMySessionsSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'session-host@test.fr']);
        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'session_host_token'));
        $this->client->request('GET', '/api/session/my-sessions');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals('session-host@test.fr', $data[0]['host']['email']);
    }

    public function testGetSessionByCodeSuccess(): void
    {
        $session = $this->sessionRepository->findOneBy(['code' => 'SESSION1']);
        $this->client->request('GET', '/api/session/SESSION1');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($session->getId()->toRfc4122(), $data['id']);
        $this->assertEquals('Session Active', $data['name']);
        $this->assertEquals('SESSION1', $data['code']);
    }

    public function testGetSessionByCodeNotFound(): void
    {
        $this->client->request('GET', '/api/session/UNKNOWN');
        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.get_by_code.error_session_not_found', $data['error']);
    }

    public function testGetParticipantsSuccess(): void
    {
        $this->client->request('GET', '/api/session/SESSION1/participants');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('participants', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertGreaterThanOrEqual(2, $data['count']);
    }

    public function testGetParticipantsSessionNotFound(): void
    {
        $this->client->request('GET', '/api/session/UNKNOWN/participants');
        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.participants.error_session_not_found', $data['error']);
    }

    public function testGetMySessionsUnauthorized(): void
    {
        $this->client->request('GET', '/api/session/my-sessions');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateSessionSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'session-host@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));

        $payload = [
            'name' => 'Nouvelle session',
            'description' => 'Description de test',
            'maxParticipants' => 5,
        ];
        $this->client->request(
            'POST',
            '/api/session',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('Nouvelle session', $data['name']);
        $this->assertEquals(5, $data['maxParticipants']);
        $this->assertArrayHasKey('host', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('code', $data);
    }

    public function testCreateSessionUnauthorized(): void
    {
        $this->client->request(
            'POST',
            '/api/session',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Session'])
        );
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateSessionInvalidData(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'session-host@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));

        $this->client->request(
            'POST',
            '/api/session',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['maxParticipants' => 0])
        );
        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testJoinSessionPseudoRequired(): void
    {
        $this->client->request(
            'POST',
            '/api/session/SESSION1/join',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );
        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.join.error.pseudo_required', $data['error']);
    }

    public function testJoinSessionNotFound(): void
    {
        $payload = ['pseudo' => 'GuestX'];
        $this->client->request(
            'POST',
            '/api/session/UNKNOWN/join',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.join.error.session_not_found', $data['error']);
    }

    public function testJoinSessionPseudoAlreadyTaken(): void
    {
        $payload = ['pseudo' => 'Guest1'];
        $this->client->request(
            'POST',
            '/api/session/SESSION1/join',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testJoinSessionThrowException(): void
    {
        $mock = $this->createMock(SessionManager::class);
        $mock->method('findSessionByCode')->willThrowException(new Exception('Erreur interne'));
        static::getContainer()->set(SessionManager::class, $mock);

        $payload = ['pseudo' => 'new guest'];
        $this->client->request(
            'POST',
            '/api/session/SESSION1/join',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseStatusCodeSame(500);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.join.error', $data['error']);
    }

    public function testJoinSessionSuccess(): void
    {
        $payload = ['pseudo' => 'new guest'];
        $this->client->request(
            'POST',
            '/api/session/SESSION1/join',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('new guest', $data['participant']['pseudo']);
        $this->assertEquals('session.join.success', $data['message']);
    }

    public function testRemoveParticipantNotHostKick(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'session-guest@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));

        $payload = ['pseudo' => 'Guest1', 'reason' => 'kick'];
        $this->client->request(
            'POST',
            '/api/session/SESSION1/remove',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.remove.errors.only_host_can_kick', $data['error']);
    }

    public function testRemoveParticipantSessionNotFound(): void
    {
        $payload = ['pseudo' => 'Guest1', 'reason' => 'kick'];
        $this->client->request(
            'POST',
            '/api/session/UNKNOWN/remove',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.remove.errors.session_not_found', $data['error']);
    }

    public function testRemoveParticipantNotFound(): void
    {
        $payload = ['pseudo' => 'Inexistant', 'reason' => 'kick'];
        $this->client->request(
            'POST',
            '/api/session/SESSION1/remove',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.remove.errors.participant_not_found', $data['error']);
    }

    public function testRemoveParticipantPseudoRequired(): void
    {
        $payload = ['reason' => 'kick'];
        $this->client->request(
            'POST',
            '/api/session/SESSION1/remove',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.remove.errors.pseudo_required', $data['error']);
    }

    public function testRemoveParticipantThrowException(): void
    {

        $mock = $this->createMock(SessionManager::class);
        $mock->method('findSessionByCode')->willThrowException(new Exception('Erreur interne'));
        static::getContainer()->set(SessionManager::class, $mock);

        $this->client->request('POST', '/api/session/SESSION1/remove', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['pseudo' => 'Guest1', 'reason' => 'kick']));
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.remove.error', $data['error']);
    }

    public function testRemoveParticipantSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'session-host@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));
        $payload = ['pseudo' => 'Guest1', 'reason' => 'kick'];
        $this->client->request(
            'POST',
            '/api/session/SESSION1/remove',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testEndSessionNotHost(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'session-guest@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));
        $this->client->request('POST', '/api/session/SESSION1/end');
        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.end.error_forbidden', $data['error']);
    }

    public function testEndSessionNotFound(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'session-host@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));
        $this->client->request('POST', '/api/session/UNKNOWN/end');
        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.end.error_session_not_found', $data['error']);
    }

    public function testEndSessionUnauthorized(): void
    {
        $this->client->request('POST', '/api/session/SESSION1/end');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testEndSessionThrowException(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'session-host@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));

        $mock = $this->createMock(SessionManager::class);
        $mock->method('findSessionByCode')->willThrowException(new Exception('Erreur interne'));
        static::getContainer()->set(SessionManager::class, $mock);

        $this->client->request('POST', '/api/session/SESSION1/end');
        $this->assertResponseStatusCodeSame(500);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.end.error', $data['error']);
    }

    public function testEndSessionSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'session-host@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));
        $this->client->request('POST', '/api/session/SESSION1/end');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('session.end.success', $data['message']);
    }
}
