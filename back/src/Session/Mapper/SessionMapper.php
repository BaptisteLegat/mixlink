<?php

namespace App\Session\Mapper;

use App\Entity\Session;
use App\Entity\User;
use App\Session\Model\Request\CreateSessionRequest;
use App\Session\Model\SessionModel;
use RuntimeException;

class SessionMapper
{
    public function mapEntity(CreateSessionRequest $request, User $host): Session
    {
        $session = (new Session())
            ->setName($request->name)
            ->setMaxParticipants($request->maxParticipants)
            ->setHost($host)
        ;

        return $session;
    }

    public function mapModel(Session $session): SessionModel
    {
        $model = (new SessionModel())
            ->setId($session->getId()?->toRfc4122() ?? '')
            ->setName($session->getName())
            ->setCode($session->getCode())
            ->setMaxParticipants($session->getMaxParticipants())
        ;

        $host = $session->getHost();
        if (null === $host) {
            throw new RuntimeException('Host is null');
        }

        $hostArray = [
            'id' => (string) $host->getId(),
            'firstName' => $host->getFirstName(),
            'lastName' => $host->getLastName(),
            'email' => $host->getEmail(),
            'profilePicture' => $host->getProfilePicture(),
            'roles' => $host->getRoles(),
        ];

        $model->setHost($hostArray);

        $model->setCreatedAt($session->getCreatedAt()?->format('c') ?? '');
        $model->setEndedAt($session->getEndedAt()?->format('c'));

        return $model;
    }

    /**
     * @param array<Session> $sessions
     *
     * @return SessionModel[]
     */
    public function mapModels(array $sessions): array
    {
        return array_map([$this, 'mapModel'], $sessions);
    }
}
