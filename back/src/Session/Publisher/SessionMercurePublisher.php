<?php

namespace App\Session\Publisher;

use App\Entity\Session;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class SessionMercurePublisher
{
    public function __construct(
        private HubInterface $mercureHub,
        private LoggerInterface $logger,
    ) {
    }

    public function publishSessionUpdate(Session $session, string $event): void
    {
        $code = $session->getCode();
        if (null === $code) {
            $this->logger->error('Session code is null, cannot publish Mercure update', [
                'event' => $event,
            ]);

            return;
        }
        $data = [
            'event' => $event,
            'session' => [
                'id' => $session->getId()?->toRfc4122(),
                'code' => $code,
                'name' => $session->getName(),
            ],
        ];
        $this->publish('session/'.$code, $data, $code, $event);
    }

    /**
     * @param array<string, mixed> $participantData
     */
    public function publishParticipantUpdate(Session $session, string $event, array $participantData): void
    {
        $code = $session->getCode();
        if (null === $code) {
            $this->logger->error('Session code is null, cannot publish Mercure participant update', [
                'event' => $event,
            ]);

            return;
        }
        $data = [
            'event' => $event,
            'participant' => $participantData,
            'session' => [
                'code' => $code,
                'participants_count' => count($session->getParticipants()),
            ],
        ];
        $this->publish('session/'.$code, $data, $code, $event);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function publish(string $topic, array $data, string $sessionCode, string $event): void
    {
        try {
            $jsonData = json_encode($data);
            if (false === $jsonData) {
                throw new RuntimeException('Failed to encode data to JSON');
            }
            $update = new Update($topic, $jsonData);
            $this->mercureHub->publish($update);
        } catch (Exception $e) {
            $this->logger->error('Failed to publish update to Mercure', [
                'sessionCode' => $sessionCode,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
