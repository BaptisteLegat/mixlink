<?php

namespace App\Session\Mapper;

use App\Entity\Session;
use App\Entity\User;
use App\Playlist\Mapper\PlaylistMapper;
use App\Playlist\PlaylistManager;
use App\Session\Model\Request\CreateSessionRequest;
use App\Session\Model\SessionModel;
use RuntimeException;

class SessionMapper
{
    public function __construct(
        private PlaylistManager $playlistManager,
        private PlaylistMapper $playlistMapper,
    ) {
    }

    public function mapEntity(CreateSessionRequest $request, User $host): Session
    {
        $session = (new Session())
            ->setName($request->getName())
            ->setMaxParticipants($request->getMaxParticipants())
            ->setHost($host)
        ;

        return $session;
    }

    public function mapModel(Session $session): SessionModel
    {
        $code = $session->getCode();
        $model = (new SessionModel())
            ->setId($session->getId()?->toRfc4122() ?? '')
            ->setName($session->getName())
            ->setCode($code)
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

        $playlist = null;
        if (null !== $code) {
            $playlistEntity = $this->playlistManager->getPlaylistBySessionCode($code);
            if (null !== $playlistEntity) {
                $playlist = $this->playlistMapper->mapModel($playlistEntity);
            }
        }

        $model->setPlaylist($playlist);

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
