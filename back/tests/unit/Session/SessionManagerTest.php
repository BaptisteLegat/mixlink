<?php

namespace App\Tests\Unit\Session;

use App\Entity\Session;
use App\Entity\User;
use App\Playlist\PlaylistManager;
use App\Repository\SessionRepository;
use App\Session\Manager\SessionManager;
use App\Session\Manager\SessionParticipantManager;
use App\Session\Mapper\SessionMapper;
use App\Session\Model\Request\CreateSessionRequest;
use App\Session\Publisher\SessionMercurePublisher;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SessionManagerTest extends TestCase
{
    private SessionRepository|MockObject $sessionRepositoryMock;
    private LoggerInterface|MockObject $loggerMock;
    private SessionMapper|MockObject $sessionMapperMock;
    private SessionMercurePublisher|MockObject $mercurePublisherMock;
    private SessionParticipantManager|MockObject $sessionParticipantManagerMock;
    private PlaylistManager|MockObject $playlistManagerMock;
    private SessionManager $sessionManager;

    protected function setUp(): void
    {
        $this->sessionRepositoryMock = $this->createMock(SessionRepository::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->sessionMapperMock = $this->createMock(SessionMapper::class);
        $this->mercurePublisherMock = $this->createMock(SessionMercurePublisher::class);
        $this->sessionParticipantManagerMock = $this->createMock(SessionParticipantManager::class);
        $this->playlistManagerMock = $this->createMock(PlaylistManager::class);
        $this->sessionManager = new SessionManager(
            $this->sessionRepositoryMock,
            $this->loggerMock,
            $this->sessionMapperMock,
            $this->mercurePublisherMock,
            $this->sessionParticipantManagerMock,
            $this->playlistManagerMock,
        );
    }

    public function testCreateSessionSuccess(): void
    {
        $host = new User()
            ->setEmail('john@doe.com')
            ->setFirstName('John')
        ;

        $request = new CreateSessionRequest('Session Test', 'Playlist Test', 5);
        $session = new Session();

        $this->sessionMapperMock->expects($this->once())
            ->method('mapEntity')
            ->with($request, $host)
            ->willReturn($session)
        ;

        $this->sessionRepositoryMock->expects($this->once())
            ->method('generateUniqueCode')
            ->willReturn('CODE123')
        ;

        $this->sessionRepositoryMock->expects($this->once())
            ->method('save')
            ->with($session, true)
        ;

        $this->playlistManagerMock->expects($this->once())
            ->method('createSessionPlaylist')
            ->with($host, 'CODE123', 'Playlist Test')
        ;

        $this->sessionParticipantManagerMock->expects($this->once())
            ->method('joinSession')
            ->with($session, 'John')
        ;

        $result = $this->sessionManager->createSession($host, $request);
        $this->assertSame($session, $result);
    }

    public function testEndSessionByHost(): void
    {
        $host = new User()->setEmail('john@doe.com');

        $session = new Session()
            ->setHost($host)
            ->setCode('CODE123')
        ;

        $this->mercurePublisherMock->expects($this->once())
            ->method('publishSessionUpdate')
            ->with($session, 'session_ended')
        ;

        $this->sessionRepositoryMock->expects($this->once())
            ->method('remove')
            ->with($session, true)
        ;

        $this->sessionManager->endSession($session, $host);
    }

    public function testEndSessionByNonHostThrowsException(): void
    {
        $host = new User()->setEmail('host@doe.com');
        $other = new User()->setEmail('other@doe.com');
        $session = new Session()->setHost($host);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only the host can end the session');
        $this->sessionManager->endSession($session, $other);
    }

    public function testDeleteSessionByHost(): void
    {
        $host = new User()->setEmail('john@doe.com');
        $session = new Session()
            ->setHost($host)
            ->setCode('CODE123')
        ;

        $this->mercurePublisherMock->expects($this->once())
            ->method('publishSessionUpdate')
            ->with($session, 'session_deleted')
        ;

        $this->sessionRepositoryMock->expects($this->once())
            ->method('remove')
            ->with($session, true)
        ;

        $this->loggerMock->expects($this->once())->method('info');
        $this->sessionManager->deleteSession($session, $host);
    }

    public function testDeleteSessionByNonHostThrowsException(): void
    {
        $host = new User()->setEmail('host@doe.com');
        $other = new User()->setEmail('other@doe.com');
        $session = new Session()->setHost($host);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only the host can delete the session');
        $this->sessionManager->deleteSession($session, $other);
    }

    public function testFindSessionByCode(): void
    {
        $session = new Session();
        $this->sessionRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'CODE123'])
            ->willReturn($session)
        ;

        $result = $this->sessionManager->findSessionByCode('CODE123');
        $this->assertSame($session, $result);
    }

    public function testFindSessionByCodeReturnsNull(): void
    {
        $this->sessionRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'CODE123'])
            ->willReturn(null)
        ;

        $result = $this->sessionManager->findSessionByCode('CODE123');
        $this->assertNull($result);
    }

    public function testGetActiveSessionsByHost(): void
    {
        $host = new User();
        $sessions = [new Session(), new Session()];
        $this->sessionRepositoryMock->expects($this->once())
            ->method('findBy')
            ->with(['host' => $host])
            ->willReturn($sessions)
        ;

        $result = $this->sessionManager->getActiveSessionsByHost($host);
        $this->assertSame($sessions, $result);
    }
}
