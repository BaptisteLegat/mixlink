<?php

namespace App\Tests\Unit\Song;

use App\Entity\Song;
use App\Repository\SongRepository;
use App\Song\SongManager;
use App\Song\SongMapper;
use App\Song\SongModel;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SongManagerTest extends TestCase
{
    private SongRepository|MockObject $songRepositoryMock;
    private SongMapper|MockObject $songMapperMock;
    private SongManager $songManager;

    protected function setUp(): void
    {
        $this->songRepositoryMock = $this->createMock(SongRepository::class);
        $this->songMapperMock = $this->createMock(SongMapper::class);
        $this->songManager = new SongManager(
            $this->songRepositoryMock,
            $this->songMapperMock
        );
    }

    public function testFindOrCreateSongWithExistingSong(): void
    {
        $songModel = new SongModel()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
            ->setImage('https://example.com/image.jpg')
        ;

        $existingSong = new Song()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $this->songRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['spotifyId' => 'spotify_id_123'])
            ->willReturn($existingSong)
        ;

        $result = $this->songManager->findOrCreateSong($songModel, 'test@example.com');

        $this->assertSame($existingSong, $result);
    }

    public function testFindOrCreateSongWithNewSong(): void
    {
        $songModel = new SongModel()
            ->setSpotifyId('spotify_id_456')
            ->setTitle('New Song')
            ->setArtists('New Artist')
            ->setImage('https://example.com/new-image.jpg')
        ;

        $newSong = new Song()
            ->setSpotifyId('spotify_id_456')
            ->setTitle('New Song')
            ->setArtists('New Artist')
        ;

        $this->songRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['spotifyId' => 'spotify_id_456'])
            ->willReturn(null)
        ;

        $this->songMapperMock->expects($this->once())
            ->method('mapEntity')
            ->with($songModel)
            ->willReturn($newSong)
        ;

        $this->songRepositoryMock->expects($this->once())
            ->method('save')
            ->with($newSong, true)
        ;

        $result = $this->songManager->findOrCreateSong($songModel, 'test@example.com');

        $this->assertSame($newSong, $result);
        $this->assertNotNull($newSong->getCreatedAt());
        $this->assertEquals('test@example.com', $newSong->getCreatedBy());
    }

    public function testFindOrCreateSongWithNullSpotifyIdThrowsException(): void
    {
        $songModel = new SongModel()
            ->setSpotifyId(null)
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Spotify ID is required');

        $this->songManager->findOrCreateSong($songModel, 'test@example.com');
    }

    public function testFindOrCreateSongWithEmptySpotifyIdThrowsException(): void
    {
        $songModel = new SongModel()
            ->setSpotifyId('')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Spotify ID is required');

        $this->songManager->findOrCreateSong($songModel, 'test@example.com');
    }
}
