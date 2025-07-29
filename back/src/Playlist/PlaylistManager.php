<?php

namespace App\Playlist;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Entity\User;
use App\Playlist\Mapper\PlaylistMapper;
use App\Playlist\Model\PlaylistModel;
use App\Repository\PlaylistRepository;
use App\Song\SongManager;
use App\Song\SongModel;
use App\Trait\TraceableTrait;
use InvalidArgumentException;

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

    public function addSongToPlaylist(Playlist $playlist, SongModel $songModel): Song
    {
        foreach ($playlist->getSongs() as $existingSong) {
            if ($existingSong->getSpotifyId() === $songModel->getSpotifyId()) {
                throw new InvalidArgumentException('Song already in playlist');
            }
        }

        $userEmail = $playlist->getUser()?->getEmail() ?? '';

        $song = $this->songManager->findOrCreateSong($songModel, $userEmail);

        $playlist->addSong($song);

        $this->playlistRepository->save($playlist, true);

        return $song;
    }

    public function removeSongFromPlaylist(Playlist $playlist, string $spotifyId): void
    {
        foreach ($playlist->getSongs() as $song) {
            if ($song->getSpotifyId() === $spotifyId) {
                $playlist->removeSong($song);
                $this->playlistRepository->save($playlist, true);

                return;
            }
        }
        throw new InvalidArgumentException('Song not found in playlist');
    }

    public function getPlaylistBySessionCode(string $sessionCode): ?Playlist
    {
        return $this->playlistRepository->findOneBySessionCode($sessionCode);
    }
}
