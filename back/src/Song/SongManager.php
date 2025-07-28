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

    /**
     * @param array<string, string|null> $data
     */
    public function findOrCreateSong(array $data): Song
    {
        if (empty($data['spotifyId']) || empty($data['title']) || empty($data['artists'])) {
            throw new InvalidArgumentException('Invalid song data');
        }

        $song = $this->songRepository->findOneBy(['spotifyId' => $data['spotifyId']]);
        if (null === $song) {
            $song = $this->songMapper->mapEntity($data);
            $this->setTimestampable($song);
            $this->setBlameable($song, $data['createdBy'] ?? '');
            $this->songRepository->save($song, true);
        }

        return $song;
    }
}
