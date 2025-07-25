<?php

namespace App\Tests\Unit\Session;

use App\Entity\Session;
use App\Entity\SessionParticipant;
use App\Repository\SessionParticipantRepository;
use App\Session\Manager\SessionParticipantManager;
use App\Session\Publisher\SessionMercurePublisher;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SessionParticipantManagerTest extends TestCase
{
    private SessionParticipantRepository|MockObject $participantRepositoryMock;
    private LoggerInterface|MockObject $loggerMock;
    private SessionMercurePublisher|MockObject $mercurePublisherMock;
    private SessionParticipantManager $participantManager;

    protected function setUp(): void
    {
        $this->participantRepositoryMock = $this->createMock(SessionParticipantRepository::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->mercurePublisherMock = $this->createMock(SessionMercurePublisher::class);
        $this->participantManager = new SessionParticipantManager(
            $this->participantRepositoryMock,
            $this->loggerMock,
            $this->mercurePublisherMock
        );
    }

    public function testJoinSessionSuccess(): void
    {
        $session = new Session()->setMaxParticipants(2);
        $pseudo = 'John';

        $this->participantRepositoryMock->expects($this->once())
            ->method('findBySessionAndPseudo')
            ->with($session, $pseudo)
            ->willReturn(null)
        ;
        $this->participantRepositoryMock->expects($this->once())
            ->method('countActiveBySession')
            ->with($session)
            ->willReturn(0)
        ;
        $this->participantRepositoryMock->expects($this->once())->method('save');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('Participant joined session'),
                $this->arrayHasKey('sessionId')
            )
        ;
        $this->mercurePublisherMock->expects($this->once())
            ->method('publishParticipantUpdate')
            ->with(
                $session,
                'participant_joined',
                ['id' => null, 'pseudo' => $pseudo]
            )
        ;

        $participant = $this->participantManager->joinSession($session, $pseudo);
        $this->assertInstanceOf(SessionParticipant::class, $participant);
        $this->assertEquals($pseudo, $participant->getPseudo());
        $this->assertSame($session, $participant->getSession());
    }

    public function testJoinSessionPseudoTakenThrowsException(): void
    {
        $session = new Session()->setMaxParticipants(2);
        $pseudo = 'John';

        $this->participantRepositoryMock->expects($this->once())
            ->method('findBySessionAndPseudo')
            ->with($session, $pseudo)
            ->willReturn(new SessionParticipant())
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('session.join.errors.pseudo_taken');
        $this->participantManager->joinSession($session, $pseudo);
    }

    public function testJoinSessionFullThrowsException(): void
    {
        $session = new Session()->setMaxParticipants(1);
        $pseudo = 'John';

        $this->participantRepositoryMock->expects($this->once())
            ->method('findBySessionAndPseudo')
            ->with($session, $pseudo)
            ->willReturn(null)
        ;
        $this->participantRepositoryMock->expects($this->once())
            ->method('countActiveBySession')
            ->with($session)
            ->willReturn(1)
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('session.join.errors.session_full');
        $this->participantManager->joinSession($session, $pseudo);
    }

    public function testRemoveParticipant(): void
    {
        $session = new Session();
        $participant = new SessionParticipant()
            ->setSession($session)
            ->setPseudo('John')
        ;

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('Participant removed from session'),
                $this->arrayHasKey('sessionId')
            )
        ;
        $this->mercurePublisherMock->expects($this->once())
            ->method('publishParticipantUpdate')
            ->with(
                $session,
                'participant_removed',
                ['id' => $participant->getId(), 'pseudo' => $participant->getPseudo(), 'reason' => 'leave']
            )
        ;

        $this->participantRepositoryMock->expects($this->once())->method('remove')->with($participant, true);
        $this->participantManager->removeParticipant($participant);
    }

    public function testRemoveParticipantWithReason(): void
    {
        $session = new Session();
        $participant = new SessionParticipant()
            ->setSession($session)
            ->setPseudo('John')
        ;

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('Participant removed from session'),
                $this->arrayHasKey('sessionId')
            )
        ;
        $this->mercurePublisherMock->expects($this->once())
            ->method('publishParticipantUpdate')
            ->with(
                $session,
                'participant_removed',
                ['id' => $participant->getId(), 'pseudo' => $participant->getPseudo(), 'reason' => 'kick']
            )
        ;

        $this->participantRepositoryMock->expects($this->once())->method('remove')->with($participant, true);
        $this->participantManager->removeParticipant($participant, 'kick');
    }

    public function testGetActiveParticipants(): void
    {
        $session = new Session();
        $participants = [
            new SessionParticipant(),
            new SessionParticipant(),
        ];

        $this->participantRepositoryMock->expects($this->once())
            ->method('findActiveBySession')
            ->with($session)
            ->willReturn($participants)
        ;

        $result = $this->participantManager->getActiveParticipants($session);
        $this->assertSame($participants, $result);
    }

    public function testGetParticipantBySessionAndPseudo(): void
    {
        $session = new Session();
        $pseudo = 'John';
        $participant = new SessionParticipant();

        $this->participantRepositoryMock->expects($this->once())
            ->method('findBySessionAndPseudo')
            ->with($session, $pseudo)
            ->willReturn($participant)
        ;

        $result = $this->participantManager->getParticipantBySessionAndPseudo($session, $pseudo);
        $this->assertSame($participant, $result);
    }
}
