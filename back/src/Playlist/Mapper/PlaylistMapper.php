<?php

namespace App\Playlist\Mapper;

use App\Entity\Playlist;
use App\Playlist\Model\PlaylistModel;
use App\Song\SongMapper;

class PlaylistMapper
{
    public function __construct(private SongMapper $songMapper)
    {
    }

    public function mapEntity(PlaylistModel $model): Playlist
    {
        return (new Playlist())
            ->setName($model->getName())
            ->setSessionCode($model->getSessionCode())
        ;
    }

    public function mapModel(Playlist $playlist): PlaylistModel
    {
        $songs = $playlist->getSongs()->toArray();
        $songModels = $this->songMapper->mapModels($songs);

        return (new PlaylistModel())
            ->setId($playlist->getId()?->toRfc4122() ?? '')
            ->setName($playlist->getName())
            ->setSessionCode($playlist->getSessionCode())
            ->setUserId($playlist->getUser()?->getId()?->toRfc4122() ?? null)
            ->setCreatedAt($playlist->getCreatedAt()?->format('c'))
            ->setUpdatedAt($playlist->getUpdatedAt()?->format('c'))
            ->setSongsCount($playlist->getSongs()->count())
            ->setSongs(array_values($songModels))
        ;
    }

    /**
     * @param Playlist[] $playlists
     *
     * @return PlaylistModel[]
     */
    public function mapModels(array $playlists): array
    {
        return array_map([$this, 'mapModel'], $playlists);
    }
}
