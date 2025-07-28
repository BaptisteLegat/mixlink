<?php

namespace App\Playlist;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Entity\User;
use App\Playlist\Mapper\PlaylistMapper;
use App\Playlist\Model\PlaylistModel;
use App\Repository\PlaylistRepository;
use App\Song\SongManager;
use App\Trait\TraceableTrait;

class PlaylistManager
{
    use TraceableTrait;

    public function __construct(
        private PlaylistRepository $playlistRepository,
        private PlaylistMapper $playlistMapper,
        private SongManager $songManager,
    ) {
    }

    public function createSessionPlaylist(User $user, string $sessionCode, string $sessionName): Playlist
    {
        $playlistModel = new PlaylistModel();
        $playlistModel->setName($sessionName);
        $playlistModel->setSessionCode($sessionCode);

        $playlist = $this->playlistMapper->mapEntity($playlistModel);
        $playlist->setUser($user);
        $user->addPlaylist($playlist);

        $this->setTimestampable($playlist);
        $this->setBlameable($playlist, $user->getEmail() ?? '');

        $this->playlistRepository->save($playlist, true);

        return $playlist;
    }

    public function deletePlaylistBySessionCode(string $sessionCode): void
    {
        $this->playlistRepository->hardDeleteBySessionCode($sessionCode);
    }

    public function findPlaylistById(string $id): ?Playlist
    {
        return $this->playlistRepository->find($id);
    }

    /**
     * @param array<string, string|null> $data
     */
    public function addSongToPlaylist(Playlist $playlist, array $data): Song
    {
        $data['createdBy'] = $playlist->getUser()?->getEmail() ?? '';
        $song = $this->songManager->findOrCreateSong($data);
        $playlist->addSong($song);
        $this->playlistRepository->save($playlist, true);

        return $song;
    }
}
