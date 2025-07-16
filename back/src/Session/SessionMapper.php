<?php

namespace App\Session;

use App\Entity\Session;
use App\Entity\User;

class SessionMapper
{
    public function mapFromRequest(CreateSessionRequest $request, User $host): Session
    {
        $session = new Session()
            ->setName($request->name)
            ->setMaxParticipants($request->maxParticipants)
            ->setHost($host)
        ;

        return $session;
    }

    public function toModel(Session $session): SessionModel
    {
        $model = new SessionModel()
            ->setId($session->getId()?->toRfc4122() ?? '')
            ->setName($session->getName())
            ->setCode($session->getCode())
            ->setMaxParticipants($session->getMaxParticipants())
        ;

        $host = $session->getHost();
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
    public function toModels(array $sessions): array
    {
        return array_map([$this, 'toModel'], $sessions);
    }
}
