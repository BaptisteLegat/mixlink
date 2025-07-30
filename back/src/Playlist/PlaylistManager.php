<?php

namespace App\Playlist;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Entity\User;
use App\Repository\PlaylistRepository;
use App\Repository\SongRepository;
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
        private SongRepository $songRepository,
    ) {
    }

    public function createSessionPlaylist(User $user, string $sessionCode, string $sessionName): Playlist
    {
        $playlistModel = (new PlaylistModel())
            ->setName($sessionName)
            ->setSessionCode($sessionCode)
        ;

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
        $this->songRepository->hardDeleteOrphanedSongs();
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
        $songToDelete = null;

        foreach ($playlist->getSongs() as $song) {
            if ($song->getSpotifyId() === $spotifyId) {
                $playlist->removeSong($song);
                $songToDelete = $song;

                break;
            }
        }

        if (null === $songToDelete) {
            throw new InvalidArgumentException('Song not found in playlist');
        }

        $this->playlistRepository->save($playlist, true);

        if ($songToDelete->getPlaylists()->isEmpty()) {
            $this->songRepository->hardDeleteBySpotifyId($spotifyId);
        }
    }

    public function getPlaylistBySessionCode(string $sessionCode): ?Playlist
    {
        return $this->playlistRepository->findOneBy(['sessionCode' => $sessionCode]);
    }
}
