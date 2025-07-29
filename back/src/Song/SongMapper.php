<?php

namespace App\Song;

use App\Entity\Song;

class SongMapper
{
    public function mapEntity(SongModel $model): Song
    {
        return (new Song())
            ->setSpotifyId($model->getSpotifyId())
            ->setTitle($model->getTitle())
            ->setArtists($model->getArtists())
            ->setImage($model->getImage())
        ;
    }

    public function mapModel(Song $song): SongModel
    {
        return (new SongModel())
            ->setSpotifyId($song->getSpotifyId())
            ->setTitle($song->getTitle())
            ->setArtists($song->getArtists())
            ->setImage($song->getImage())
            ->setCreatedAt($song->getCreatedAt()?->format('c'))
        ;
    }

    /**
     * @param Song[] $songs
     *
     * @return SongModel[]
     */
    public function mapModels(array $songs): array
    {
        return array_map([$this, 'mapModel'], $songs);
    }
}
