<?php

namespace App\Session\Manager;

use App\Entity\Session;
use App\Entity\User;
use App\Playlist\PlaylistManager;
use App\Repository\SessionRepository;
use App\Session\Mapper\SessionMapper;
use App\Session\Model\Request\CreateSessionRequest;
use App\Session\Publisher\SessionMercurePublisher;
use App\Trait\TraceableTrait;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class SessionManager
{
    use TraceableTrait;

    public function __construct(
        private SessionRepository $sessionRepository,
        private LoggerInterface $logger,
        private SessionMapper $sessionMapper,
        private SessionMercurePublisher $mercurePublisher,
        private SessionParticipantManager $sessionParticipantManager,
        private PlaylistManager $playlistManager,
    ) {
    }

    public function createSession(User $host, CreateSessionRequest $request): Session
    {
        $session = $this->sessionMapper->mapEntity($request, $host);

        $code = $this->sessionRepository->generateUniqueCode();
        $session->setCode($code);

        $this->setTimestampable($session, false);
        $this->setBlameable($session, $host->getEmail() ?? '', false);

        $this->sessionRepository->save($session, true);

        $this->playlistManager->createSessionPlaylist($host, $code, $request->getPlaylistName());

        $this->sessionParticipantManager->joinSession($session, (string) $host->getFirstName());

        return $session;
    }

    public function endSession(Session $session, User $user): void
    {
        if ($session->getHost() !== $user) {
            throw new InvalidArgumentException('Only the host can end the session');
        }

        $sessionCode = $session->getCode();

        $this->mercurePublisher->publishSessionUpdate($session, 'session_ended');

        $this->sessionRepository->remove($session, true);

        if (null !== $sessionCode) {
            $this->playlistManager->deletePlaylistBySessionCode($sessionCode);
        }
    }

    public function deleteSession(Session $session, User $user): void
    {
        if ($session->getHost() !== $user) {
            throw new InvalidArgumentException('Only the host can delete the session');
        }

        $sessionId = $session->getId()?->toRfc4122();

        $this->mercurePublisher->publishSessionUpdate($session, 'session_deleted');

        $this->sessionRepository->remove($session, true);

        $this->logger->info('Session deleted', [
            'sessionId' => $sessionId,
            'hostId' => $user->getId()?->toRfc4122(),
        ]);
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
