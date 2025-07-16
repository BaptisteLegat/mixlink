<?php

namespace App\Session;

use App\Entity\Session;
use App\Entity\User;
use App\Repository\SessionRepository;
use App\Trait\TraceableTrait;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class SessionManager
{
    use TraceableTrait;

    public function __construct(
        private SessionRepository $sessionRepository,
        private LoggerInterface $logger,
        private SessionMapper $sessionMapper,
        private HubInterface $mercureHub,
    ) {
    }

    public function createSession(User $host, CreateSessionRequest $request): Session
    {
        $session = $this->sessionMapper->mapFromRequest($request, $host);
        $session->setCode($this->sessionRepository->generateUniqueCode());

        $this->setTimestampable($session, false);
        $this->setBlameable($session, $host->getEmail(), false);

        $this->sessionRepository->save($session, true);

        $this->logger->info('Session created', [
            'sessionId' => $session->getId()?->toRfc4122(),
            'hostId' => $host->getId()?->toRfc4122(),
            'sessionCode' => $session->getCode(),
        ]);

        return $session;
    }

    public function endSession(Session $session, User $user): void
    {
        if ($session->getHost() !== $user) {
            throw new InvalidArgumentException('Only the host can end the session');
        }

        $sessionId = $session->getId()?->toRfc4122();
        $sessionCode = $session->getCode();

        $this->publishSessionUpdate($session, 'session_ended');

        $this->sessionRepository->remove($session, true);

        $this->logger->info('Session ended and deleted', [
            'sessionId' => $sessionId,
            'hostId' => $user->getId()?->toRfc4122(),
            'sessionCode' => $sessionCode,
        ]);
    }

    public function deleteSession(Session $session, User $user): void
    {
        if ($session->getHost() !== $user) {
            throw new InvalidArgumentException('Only the host can delete the session');
        }

        $sessionId = $session->getId()?->toRfc4122();

        $this->publishSessionUpdate($session, 'session_deleted');

        $this->sessionRepository->remove($session, true);

        $this->logger->info('Session deleted', [
            'sessionId' => $sessionId,
            'hostId' => $user->getId()?->toRfc4122(),
        ]);
    }

    private function publishSessionUpdate(Session $session, string $event): void
    {
        try {
            $data = [
                'event' => $event,
                'session' => [
                    'id' => $session->getId()?->toRfc4122(),
                    'code' => $session->getCode(),
                    'name' => $session->getName(),
                ],
            ];

            $jsonData = json_encode($data);
            if ($jsonData === false) {
                throw new RuntimeException('Failed to encode session data to JSON');
            }

            $update = new Update(
                'session/'.$session->getCode(),
                $jsonData
            );

            $this->mercureHub->publish($update);
        } catch (Exception $e) {
            $this->logger->error('Failed to publish session update to Mercure', [
                'sessionCode' => $session->getCode(),
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function findSessionByCode(string $code): ?Session
    {
        return $this->sessionRepository->findOneBy(['code' => $code]);
    }

    /**
     * @return Session[]
     */
    public function getActiveSessionsByHost(User $host): array
    {
        return $this->sessionRepository->findBy(['host' => $host]);
    }
}
