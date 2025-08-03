<?php

namespace App\Song;

use App\Entity\Song;
use App\Repository\SongRepository;
use App\Trait\TraceableTrait;
use InvalidArgumentException;

class SongManager
{
    use TraceableTrait;

    public function __construct(
        private SongRepository $songRepository,
        private SongMapper $songMapper,
    ) {
    }

    public function findOrCreateSong(SongModel $songModel, string $userEmail): Song
    {
        if (null === $songModel->getSpotifyId() || '' === $songModel->getSpotifyId()) {
            throw new InvalidArgumentException('Spotify ID is required');
        }

        $song = $this->songRepository->findOneBy(['spotifyId' => $songModel->getSpotifyId()]);
        if (null === $song) {
            $song = $this->songMapper->mapEntity($songModel);
            $this->setTimestampable($song);
            $this->setBlameable($song, $userEmail);
            $this->songRepository->save($song, true);
        }

        return $song;
    }
}
