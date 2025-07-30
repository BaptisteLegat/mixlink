<?php

namespace App\Tests\Unit\Playlist;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Entity\User;
use App\Playlist\PlaylistManager;
use App\Playlist\PlaylistMapper;
use App\Playlist\PlaylistModel;
use App\Repository\PlaylistRepository;
use App\Repository\SongRepository;
use App\Song\SongManager;
use App\Song\SongModel;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaylistManagerTest extends TestCase
{
    private PlaylistRepository|MockObject $playlistRepositoryMock;
    private PlaylistMapper|MockObject $playlistMapperMock;
    private SongManager|MockObject $songManagerMock;
    private SongRepository|MockObject $songRepositoryMock;
    private PlaylistManager $playlistManager;

    protected function setUp(): void
    {
        $this->playlistRepositoryMock = $this->createMock(PlaylistRepository::class);
        $this->playlistMapperMock = $this->createMock(PlaylistMapper::class);
        $this->songManagerMock = $this->createMock(SongManager::class);
        $this->songRepositoryMock = $this->createMock(SongRepository::class);
        $this->playlistManager = new PlaylistManager(
            $this->playlistRepositoryMock,
            $this->playlistMapperMock,
            $this->songManagerMock,
            $this->songRepositoryMock
        );
    }

    public function testCreateSessionPlaylist(): void
    {
        $user = new User()
            ->setEmail('test@example.com')
            ->setFirstName('John')
            ->setLastName('Doe')
        ;

        $sessionCode = 'CODE123';
        $sessionName = 'Test Session';

        $playlistModel = new PlaylistModel()
            ->setName($sessionName)
            ->setSessionCode($sessionCode)
        ;

        $playlist = new Playlist()
            ->setName($sessionName)
            ->setSessionCode($sessionCode)
        ;

        $this->playlistMapperMock->expects($this->once())
            ->method('mapEntity')
            ->with($playlistModel)
            ->willReturn($playlist)
        ;

        $this->playlistRepositoryMock->expects($this->once())
            ->method('save')
            ->with($playlist, true)
        ;

        $result = $this->playlistManager->createSessionPlaylist($user, $sessionCode, $sessionName);

        $this->assertSame($playlist, $result);
        $this->assertSame($user, $playlist->getUser());
        $this->assertEquals($sessionName, $playlist->getName());
        $this->assertEquals($sessionCode, $playlist->getSessionCode());
        $this->assertNotNull($playlist->getCreatedAt());
        $this->assertEquals('test@example.com', $playlist->getCreatedBy());
    }

    public function testDeletePlaylistBySessionCode(): void
    {
        $sessionCode = 'CODE123';

        $this->playlistRepositoryMock->expects($this->once())
            ->method('hardDeleteBySessionCode')
            ->with($sessionCode)
        ;

        $this->songRepositoryMock->expects($this->once())
            ->method('hardDeleteOrphanedSongs')
        ;

        $this->playlistManager->deletePlaylistBySessionCode($sessionCode);
    }

    public function testAddSongToPlaylistSuccess(): void
    {
        $user = new User()->setEmail('test@example.com');
        $playlist = new Playlist()
            ->setUser($user)
            ->setName('Test Playlist')
        ;

        $songModel = new SongModel()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $song = new Song()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $this->songManagerMock->expects($this->once())
            ->method('findOrCreateSong')
            ->with($songModel, 'test@example.com')
            ->willReturn($song)
        ;

        $this->playlistRepositoryMock->expects($this->once())
            ->method('save')
            ->with($playlist, true)
        ;

        $result = $this->playlistManager->addSongToPlaylist($playlist, $songModel);

        $this->assertSame($song, $result);
        $this->assertTrue($playlist->getSongs()->contains($song));
    }

    public function testAddSongToPlaylistWithExistingSongThrowsException(): void
    {
        $user = new User()->setEmail('test@example.com');
        $playlist = new Playlist()
            ->setUser($user)
            ->setName('Test Playlist')
        ;

        $existingSong = new Song()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Existing Song')
            ->setArtists('Existing Artist')
        ;

        $playlist->addSong($existingSong);

        $songModel = new SongModel()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Song already in playlist');

        $this->playlistManager->addSongToPlaylist($playlist, $songModel);
    }

    public function testAddSongToPlaylistWithUserWithoutEmail(): void
    {
        $user = new User();
        $playlist = new Playlist()
            ->setUser($user)
            ->setName('Test Playlist')
        ;

        $songModel = new SongModel()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $song = new Song()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $this->songManagerMock->expects($this->once())
            ->method('findOrCreateSong')
            ->with($songModel, '')
            ->willReturn($song)
        ;

        $this->playlistRepositoryMock->expects($this->once())
            ->method('save')
            ->with($playlist, true)
        ;

        $result = $this->playlistManager->addSongToPlaylist($playlist, $songModel);

        $this->assertSame($song, $result);
    }

    public function testRemoveSongFromPlaylistSuccess(): void
    {
        $user = new User()->setEmail('test@example.com');
        $playlist = new Playlist()
            ->setUser($user)
            ->setName('Test Playlist')
        ;

        $song = new Song()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist->addSong($song);

        $this->playlistRepositoryMock->expects($this->once())
            ->method('save')
            ->with($playlist, true)
        ;

        $this->songRepositoryMock->expects($this->once())
            ->method('hardDeleteBySpotifyId')
            ->with('spotify_id_123')
        ;

        $this->playlistManager->removeSongFromPlaylist($playlist, 'spotify_id_123');

        $this->assertFalse($playlist->getSongs()->contains($song));
    }

    public function testRemoveSongFromPlaylistWithSongNotFoundThrowsException(): void
    {
        $user = new User()->setEmail('test@example.com');
        $playlist = new Playlist()
            ->setUser($user)
            ->setName('Test Playlist')
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Song not found in playlist');

        $this->playlistManager->removeSongFromPlaylist($playlist, 'nonexistent_id');
    }

    public function testRemoveSongFromPlaylistWithSongStillReferenced(): void
    {
        $user = new User()->setEmail('test@example.com');
        $playlist = new Playlist()
            ->setUser($user)
            ->setName('Test Playlist')
        ;

        $song = new Song()
            ->setSpotifyId('spotify_id_123')
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist->addSong($song);

        $otherPlaylist = new Playlist()
            ->setName('Other Playlist')
            ->setSessionCode('CODE123')
        ;

        $otherPlaylist->addSong($song);

        $this->playlistRepositoryMock->expects($this->once())
            ->method('save')
            ->with($playlist, true)
        ;

        $this->songRepositoryMock->expects($this->never())
            ->method('hardDeleteBySpotifyId')
        ;

        $this->playlistManager->removeSongFromPlaylist($playlist, 'spotify_id_123');

        $this->assertFalse($playlist->getSongs()->contains($song));
    }

    public function testGetPlaylistBySessionCode(): void
    {
        $sessionCode = 'CODE123';
        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->setSessionCode($sessionCode)
        ;

        $this->playlistRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['sessionCode' => $sessionCode])
            ->willReturn($playlist)
        ;

        $result = $this->playlistManager->getPlaylistBySessionCode($sessionCode);

        $this->assertSame($playlist, $result);
    }

    public function testGetPlaylistBySessionCodeReturnsNull(): void
    {
        $sessionCode = 'CODE123';

        $this->playlistRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['sessionCode' => $sessionCode])
            ->willReturn(null)
        ;

        $result = $this->playlistManager->getPlaylistBySessionCode($sessionCode);

        $this->assertNull($result);
    }
}
