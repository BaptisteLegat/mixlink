<?php

namespace App\Session;

use App\Entity\Session;
use App\Entity\User;
use App\User\UserMapper;
use App\User\UserModel;

class SessionMapper
{
    public function __construct(
        private UserMapper $userMapper,
    ) {
    }

    public function mapFromRequest(CreateSessionRequest $request, User $host): Session
    {
        $session = new Session()
            ->setName($request->name)
            ->setMaxParticipants($request->maxParticipants)
            ->setHost($host)
            ->setIsActive(true)
        ;

        return $session;
    }

    public function toModel(Session $session): SessionModel
    {
        $model = new SessionModel()
            ->setId($session->getId()?->toRfc4122() ?? '')
            ->setName($session->getName())
            ->setCode($session->getCode())
            ->setIsActive($session->isActive())
            ->setMaxParticipants($session->getMaxParticipants())
        ;

        $userModel = new UserModel();
        $mappedUser = $this->userMapper->mapModel($userModel, $session->getHost());
        $model->setHost($mappedUser->toArray());

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
