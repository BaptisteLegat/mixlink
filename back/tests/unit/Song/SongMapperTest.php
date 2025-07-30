<?php

namespace App\Tests\Unit\Song;

use App\Entity\Song;
use App\Song\SongMapper;
use App\Song\SongModel;
use DateTime;
use PHPUnit\Framework\TestCase;

class SongMapperTest extends TestCase
{
    private SongMapper $songMapper;

    protected function setUp(): void
    {
        $this->songMapper = new SongMapper();
    }

    public function testMapEntity(): void
    {
        $songModel = new SongModel()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
            ->setImage('https://example.com/image.jpg')
        ;

        $song = $this->songMapper->mapEntity($songModel);

        $this->assertInstanceOf(Song::class, $song);
        $this->assertEquals('spotify_id_123', $song->getSpotifyId());
        $this->assertEquals('Test Song', $song->getTitle());
        $this->assertEquals('Test Artist', $song->getArtists());
        $this->assertEquals('https://example.com/image.jpg', $song->getImage());
    }

    public function testMapEntityWithNullValues(): void
    {
        $songModel = new SongModel()
            ->setSpotifyId('spotify_id_456')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $song = $this->songMapper->mapEntity($songModel);

        $this->assertInstanceOf(Song::class, $song);
        $this->assertEquals('spotify_id_456', $song->getSpotifyId());
        $this->assertEquals('Test Song', $song->getTitle());
        $this->assertEquals('Test Artist', $song->getArtists());
        $this->assertNull($song->getImage());
    }

    public function testMapModel(): void
    {
        $createdAt = new DateTime('2024-01-01T12:00:00+00:00');

        $song = new Song()
            ->setSpotifyId('spotify_id_789')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
            ->setImage('https://example.com/image.jpg')
            ->setCreatedAt($createdAt)
        ;

        $songModel = $this->songMapper->mapModel($song);

        $this->assertInstanceOf(SongModel::class, $songModel);
        $this->assertEquals('spotify_id_789', $songModel->getSpotifyId());
        $this->assertEquals('Test Song', $songModel->getTitle());
        $this->assertEquals('Test Artist', $songModel->getArtists());
        $this->assertEquals('https://example.com/image.jpg', $songModel->getImage());
        $this->assertEquals($createdAt->format('c'), $songModel->getCreatedAt());
    }

    public function testMapModelWithNullCreatedAt(): void
    {
        $song = new Song()
            ->setSpotifyId('spotify_id_789')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
            ->setImage('https://example.com/image.jpg')
        ;

        $songModel = $this->songMapper->mapModel($song);

        $this->assertInstanceOf(SongModel::class, $songModel);
        $this->assertEquals('spotify_id_789', $songModel->getSpotifyId());
        $this->assertEquals('Test Song', $songModel->getTitle());
        $this->assertEquals('Test Artist', $songModel->getArtists());
        $this->assertEquals('https://example.com/image.jpg', $songModel->getImage());
        $this->assertNull($songModel->getCreatedAt());
    }

    public function testMapModelWithNullValues(): void
    {
        $song = new Song()
            ->setSpotifyId('spotify_id_789')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $songModel = $this->songMapper->mapModel($song);

        $this->assertInstanceOf(SongModel::class, $songModel);
        $this->assertEquals('spotify_id_789', $songModel->getSpotifyId());
        $this->assertEquals('Test Song', $songModel->getTitle());
        $this->assertEquals('Test Artist', $songModel->getArtists());
        $this->assertNull($songModel->getImage());
        $this->assertNull($songModel->getCreatedAt());
    }

    public function testMapModels(): void
    {
        $song1 = new Song()
            ->setSpotifyId('spotify_id_1')
            ->setTitle('Song 1')
            ->setArtists('Artist 1')
        ;

        $song2 = new Song()
            ->setSpotifyId('spotify_id_2')
            ->setTitle('Song 2')
            ->setArtists('Artist 2')
        ;

        $songModels = $this->songMapper->mapModels([$song1, $song2]);

        $this->assertCount(2, $songModels);
        $this->assertContainsOnlyInstancesOf(SongModel::class, $songModels);

        $this->assertEquals('spotify_id_1', $songModels[0]->getSpotifyId());
        $this->assertEquals('Song 1', $songModels[0]->getTitle());
        $this->assertEquals('Artist 1', $songModels[0]->getArtists());

        $this->assertEquals('spotify_id_2', $songModels[1]->getSpotifyId());
        $this->assertEquals('Song 2', $songModels[1]->getTitle());
        $this->assertEquals('Artist 2', $songModels[1]->getArtists());
    }

    public function testMapModelsWithEmptyArray(): void
    {
        $songModels = $this->songMapper->mapModels([]);

        $this->assertIsArray($songModels);
        $this->assertCount(0, $songModels);
    }
}
