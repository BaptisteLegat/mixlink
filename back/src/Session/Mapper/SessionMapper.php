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
        $session = new Session();
        $session->setName($request->name);
        $session->setMaxParticipants($request->maxParticipants);
        $session->setHost($host);

        return $session;
    }

    public function mapModel(Session $session): SessionModel
    {
        $model = new SessionModel();
        $model->setId($session->getId()?->toRfc4122() ?? '');
        $model->setName($session->getName());
        $model->setCode($session->getCode());
        $model->setMaxParticipants($session->getMaxParticipants());

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
