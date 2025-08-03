<?php

namespace App\Tests\Unit\Playlist;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Entity\User;
use App\Playlist\PlaylistMapper;
use App\Playlist\PlaylistModel;
use App\Song\SongMapper;
use App\Song\SongModel;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaylistMapperTest extends TestCase
{
    private SongMapper|MockObject $songMapperMock;
    private PlaylistMapper $playlistMapper;

    protected function setUp(): void
    {
        $this->songMapperMock = $this->createMock(SongMapper::class);
        $this->playlistMapper = new PlaylistMapper($this->songMapperMock);
    }

    public function testMapEntity(): void
    {
        $playlistModel = new PlaylistModel()
            ->setName('Test Playlist')
            ->setSessionCode('CODE123')
        ;

        $playlist = $this->playlistMapper->mapEntity($playlistModel);

        $this->assertInstanceOf(Playlist::class, $playlist);
        $this->assertEquals('Test Playlist', $playlist->getName());
        $this->assertEquals('CODE123', $playlist->getSessionCode());
    }

    public function testMapModel(): void
    {
        $user = new User()
            ->setEmail('test@example.com')
            ->setFirstName('John')
            ->setLastName('Doe')
        ;

        $createdAt = new DateTime('2024-01-01T12:00:00+00:00');
        $updatedAt = new DateTime('2024-01-02T12:00:00+00:00');

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->setSessionCode('CODE123')
            ->setUser($user)
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt)
        ;

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

        $playlist->addSong($song1);
        $playlist->addSong($song2);

        $songModel1 = new SongModel()
            ->setSpotifyId('spotify_id_1')
            ->setTitle('Song 1')
            ->setArtists('Artist 1')
        ;

        $songModel2 = new SongModel()
            ->setSpotifyId('spotify_id_2')
            ->setTitle('Song 2')
            ->setArtists('Artist 2')
        ;

        $this->songMapperMock->expects($this->once())
            ->method('mapModels')
            ->with([$song1, $song2])
            ->willReturn([$songModel1, $songModel2])
        ;

        $playlistModel = $this->playlistMapper->mapModel($playlist);

        $this->assertInstanceOf(PlaylistModel::class, $playlistModel);
        $this->assertEquals($playlist->getId()?->toRfc4122() ?? '', $playlistModel->getId());
        $this->assertEquals('Test Playlist', $playlistModel->getName());
        $this->assertEquals('CODE123', $playlistModel->getSessionCode());
        $this->assertEquals($user->getId()?->toRfc4122() ?? null, $playlistModel->getUserId());
        $this->assertEquals($createdAt->format('c'), $playlistModel->getCreatedAt());
        $this->assertEquals($updatedAt->format('c'), $playlistModel->getUpdatedAt());
        $this->assertEquals(2, $playlistModel->getSongsCount());
        $this->assertEquals([$songModel1, $songModel2], $playlistModel->getSongs());
    }

    public function testMapModelWithNullValues(): void
    {
        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->setSessionCode('CODE123')
        ;

        $this->songMapperMock->expects($this->once())
            ->method('mapModels')
            ->with([])
            ->willReturn([])
        ;

        $playlistModel = $this->playlistMapper->mapModel($playlist);

        $this->assertInstanceOf(PlaylistModel::class, $playlistModel);
        $this->assertEquals($playlist->getId()?->toRfc4122() ?? '', $playlistModel->getId());
        $this->assertEquals('Test Playlist', $playlistModel->getName());
        $this->assertEquals('CODE123', $playlistModel->getSessionCode());
        $this->assertNull($playlistModel->getUserId());
        $this->assertNull($playlistModel->getCreatedAt());
        $this->assertNull($playlistModel->getUpdatedAt());
        $this->assertEquals(0, $playlistModel->getSongsCount());
        $this->assertEquals([], $playlistModel->getSongs());
    }

    public function testMapModelWithUserWithoutId(): void
    {
        $user = new User()
            ->setEmail('test@example.com')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->setSessionCode('CODE123')
            ->setUser($user)
        ;

        $this->songMapperMock->expects($this->once())
            ->method('mapModels')
            ->with([])
            ->willReturn([])
        ;

        $playlistModel = $this->playlistMapper->mapModel($playlist);

        $this->assertInstanceOf(PlaylistModel::class, $playlistModel);
        $this->assertNull($playlistModel->getUserId());
    }

    public function testMapModels(): void
    {
        $playlist1 = new Playlist()
            ->setName('Playlist 1')
            ->setSessionCode('CODE1')
        ;

        $playlist2 = new Playlist()
            ->setName('Playlist 2')
            ->setSessionCode('CODE2')
        ;

        $this->songMapperMock->expects($this->exactly(2))
            ->method('mapModels')
            ->willReturnOnConsecutiveCalls([], [])
        ;

        $playlistModels = $this->playlistMapper->mapModels([$playlist1, $playlist2]);

        $this->assertCount(2, $playlistModels);
        $this->assertContainsOnlyInstancesOf(PlaylistModel::class, $playlistModels);
        $this->assertEquals('Playlist 1', $playlistModels[0]->getName());
        $this->assertEquals('CODE1', $playlistModels[0]->getSessionCode());
        $this->assertEquals('Playlist 2', $playlistModels[1]->getName());
        $this->assertEquals('CODE2', $playlistModels[1]->getSessionCode());
    }

    public function testMapModelsWithEmptyArray(): void
    {
        $playlistModels = $this->playlistMapper->mapModels([]);

        $this->assertIsArray($playlistModels);
        $this->assertCount(0, $playlistModels);
    }
}
