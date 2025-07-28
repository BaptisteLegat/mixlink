<?php

namespace App\Song;

use App\Entity\Song;

class SongMapper
{
    /**
     * @param array<string, string|null> $data
     */
    public function mapEntity(array $data): Song
    {
        return (new Song())
            ->setSpotifyId($data['spotifyId'] ?? null)
            ->setTitle($data['title'] ?? null)
            ->setArtists($data['artists'] ?? null)
            ->setImage($data['image'] ?? null)
            ->setExternalUrl($data['externalUrl'] ?? null)
        ;
    }

    public function mapModel(Song $song): SongModel
    {
        return (new SongModel())
            ->setId($song->getId()?->toRfc4122() ?? '')
            ->setSpotifyId($song->getSpotifyId())
            ->setTitle($song->getTitle())
            ->setArtists($song->getArtists())
            ->setImage($song->getImage())
            ->setExternalUrl($song->getExternalUrl())
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
