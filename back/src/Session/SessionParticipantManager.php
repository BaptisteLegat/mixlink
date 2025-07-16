<?php

namespace App\Session;

use App\Entity\Session;
use App\Entity\SessionParticipant;
use App\Repository\SessionParticipantRepository;
use App\Trait\TraceableTrait;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class SessionParticipantManager
{
    use TraceableTrait;

    public function __construct(
        private SessionParticipantRepository $participantRepository,
        private LoggerInterface $logger,
        private HubInterface $mercureHub,
    ) {
    }

    public function joinSession(Session $session, string $pseudo): SessionParticipant
    {
        $existingParticipant = $this->participantRepository->findBySessionAndPseudo($session, $pseudo);
        if ($existingParticipant) {
            throw new InvalidArgumentException('Ce pseudo est déjà pris dans cette session');
        }

        $currentCount = $this->participantRepository->countActiveBySession($session);
        if ($currentCount >= $session->getMaxParticipants()) {
            throw new InvalidArgumentException('La session est pleine');
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

        $this->publishParticipantUpdate($session, 'participant_joined', $participant);

        return $participant;
    }

    public function leaveSession(SessionParticipant $participant): void
    {
        $session = $participant->getSession();
        $pseudo = $participant->getPseudo();
        $participantId = $participant->getId()?->toRfc4122();

        $this->logger->info('Participant left session', [
            'sessionId' => $session->getId()?->toRfc4122(),
            'participantId' => $participantId,
            'pseudo' => $pseudo,
        ]);

        $this->participantRepository->remove($participant, true);

        $this->publishParticipantUpdate($session, 'participant_left', $participant);
    }

    /**
     * @return SessionParticipant[]
     */
    public function getActiveParticipants(Session $session): array
    {
        return $this->participantRepository->findBy(['session' => $session]);
    }

    public function getParticipantBySessionAndPseudo(Session $session, string $pseudo): ?SessionParticipant
    {
        return $this->participantRepository->findBySessionAndPseudo($session, $pseudo);
    }

    private function publishParticipantUpdate(Session $session, string $event, SessionParticipant $participant): void
    {
        try {
            $data = [
                'event' => $event,
                'participant' => [
                    'id' => $participant->getId()?->toRfc4122(),
                    'pseudo' => $participant->getPseudo(),
                    'joinedAt' => $participant->getCreatedAt()?->format('c'),
                ],
                'session' => [
                    'code' => $session->getCode(),
                    'participantCount' => $this->participantRepository->countActiveBySession($session),
                ],
            ];

            $jsonData = json_encode($data);
            if ($jsonData === false) {
                throw new RuntimeException('Failed to encode participant data to JSON');
            }

            $update = new Update(
                'session/'.$session->getCode(),
                $jsonData
            );

            $this->mercureHub->publish($update);
        } catch (Exception $e) {
            $this->logger->error('Failed to publish participant update to Mercure', [
                'sessionCode' => $session->getCode(),
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
