<?php

namespace App\Tests\Unit\Session;

use App\Entity\Session;
use App\Session\Publisher\SessionMercurePublisher;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class SessionMercurePublisherTest extends TestCase
{
    private HubInterface|MockObject $mercureHubMock;
    private LoggerInterface|MockObject $loggerMock;
    private SessionMercurePublisher $publisher;

    protected function setUp(): void
    {
        $this->mercureHubMock = $this->createMock(HubInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->publisher = new SessionMercurePublisher(
            $this->mercureHubMock,
            $this->loggerMock
        );
    }

    public function testPublishSessionUpdatePublishesCorrectData(): void
    {
        $session = new Session()
            ->setCode('CODE123')
            ->setName('Session Test')
        ;

        $this->mercureHubMock->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $data = json_decode($update->getData(), true);

                return 'session_update' === $data['event'] && 'CODE123' === $data['session']['code'];
            }))
        ;
        $this->publisher->publishSessionUpdate($session, 'session_update');
    }

    public function testPublishSessionUpdateWithNullCodeLogsError(): void
    {
        $session = new Session();

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Session code is null, cannot publish Mercure update'),
                $this->arrayHasKey('event')
            )
        ;
        $this->publisher->publishSessionUpdate($session, 'session_update');
    }

    public function testPublishParticipantUpdatePublishesCorrectData(): void
    {
        $session = new Session()->setCode('CODE123');

        $participantData = ['id' => 'uuid', 'pseudo' => 'John'];
        $this->mercureHubMock->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $data = json_decode($update->getData(), true);

                return 'participant_joined' === $data['event'] && 'John' === $data['participant']['pseudo'];
            }))
        ;
        $this->publisher->publishParticipantUpdate($session, 'participant_joined', $participantData);
    }

    public function testPublishParticipantUpdateWithNullCodeLogsError(): void
    {
        $session = new Session();

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Session code is null, cannot publish Mercure participant update'),
                $this->arrayHasKey('event')
            )
        ;

        $this->publisher->publishParticipantUpdate($session, 'participant_joined', ['id' => 'uuid', 'pseudo' => 'John']);
    }

    public function testPublishHandlesRuntimeExceptionOnJsonEncode(): void
    {
        $session = new Session()
            ->setCode('CODE123')
            ->setName('Session Test')
        ;

        $reflection = new ReflectionClass($this->publisher);
        $method = $reflection->getMethod('publish');
        $method->setAccessible(true);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Failed to publish update to Mercure'),
                $this->arrayHasKey('sessionCode')
            )
        ;
        $method->invoke($this->publisher, 'session/CODE123', ['event' => 'test', 'session' => fopen('php://memory', 'r')], 'CODE123', 'test');
    }

    public function testPublishParticipantUpdateHandlesExceptionOnMercurePublish(): void
    {
        $session = new Session()->setCode('CODE123');

        $this->mercureHubMock->expects($this->once())
            ->method('publish')
            ->willThrowException(new Exception('Mercure error'))
        ;

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Failed to publish update to Mercure'),
                $this->arrayHasKey('sessionCode')
            )
        ;
        $this->publisher->publishParticipantUpdate($session, 'participant_joined', ['id' => 1, 'pseudo' => 'John']);
    }
}
