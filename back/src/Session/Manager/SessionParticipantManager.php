<?php

namespace App\Session\Manager;

use App\Entity\Session;
use App\Entity\SessionParticipant;
use App\Repository\SessionParticipantRepository;
use App\Session\Publisher\SessionMercurePublisher;
use App\Trait\TraceableTrait;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class SessionParticipantManager
{
    use TraceableTrait;

    public function __construct(
        private SessionParticipantRepository $participantRepository,
        private LoggerInterface $logger,
        private SessionMercurePublisher $mercurePublisher,
    ) {
    }

    public function joinSession(Session $session, string $pseudo): SessionParticipant
    {
        $existingParticipant = $this->participantRepository->findBySessionAndPseudo($session, $pseudo);
        if ($existingParticipant) {
            throw new InvalidArgumentException('session.join.errors.pseudo_taken');
        }

        $currentCount = $this->participantRepository->countActiveBySession($session);
        if ($currentCount >= $session->getMaxParticipants()) {
            throw new InvalidArgumentException('session.join.errors.session_full');
        }

        $participant = new SessionParticipant();
        $participant->setSession($session);
        $participant->setPseudo($pseudo);

        $this->setTimestampable($participant, false);
        $this->setBlameable($participant, $pseudo, false);

        $this->participantRepository->save($participant, true);

        $this->logger->info('Participant joined session', [
            'sessionId' => $session->getId()?->toRfc4122(),
            'participantId' => $participant->getId()?->toRfc4122(),
            'pseudo' => $pseudo,
        ]);

        $this->mercurePublisher->publishParticipantUpdate($session, 'participant_joined', [
            'id' => $participant->getId()?->toRfc4122(),
            'pseudo' => $participant->getPseudo(),
        ]);

        return $participant;
    }

    public function removeParticipant(SessionParticipant $participant, string $reason = 'leave'): void
    {
        $session = $participant->getSession();
        $pseudo = $participant->getPseudo();
        $participantId = $participant->getId()?->toRfc4122();

        $this->logger->info('Participant removed from session', [
            'sessionId' => $session->getId()?->toRfc4122(),
            'participantId' => $participantId,
            'pseudo' => $pseudo,
            'reason' => $reason,
        ]);

        $this->mercurePublisher->publishParticipantUpdate($session, 'participant_removed', [
            'id' => $participant->getId()?->toRfc4122(),
            'pseudo' => $participant->getPseudo(),
            'reason' => $reason,
        ]);

        $this->participantRepository->remove($participant, true);
    }

    /**
     * @return SessionParticipant[]
     */
    public function getActiveParticipants(Session $session): array
    {
        return $this->participantRepository->findActiveBySession($session);
    }

    public function getParticipantBySessionAndPseudo(Session $session, string $pseudo): ?SessionParticipant
    {
        return $this->participantRepository->findBySessionAndPseudo($session, $pseudo);
    }
}
