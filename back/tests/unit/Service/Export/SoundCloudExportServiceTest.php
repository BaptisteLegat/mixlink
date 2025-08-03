<?php

namespace App\Tests\Unit\Service\Export;

use App\Entity\Playlist;
use App\Entity\Provider;
use App\Entity\Song;
use App\Entity\User;
use App\Service\Export\Model\ExportResult;
use App\Service\Export\SoundCloud\SoundCloudPlaylistManager;
use App\Service\Export\SoundCloud\SoundCloudTrackSearcher;
use App\Service\Export\SoundCloudExportService;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SoundCloudExportServiceTest extends TestCase
{
    private SoundCloudExportService $soundCloudExportService;
    private SoundCloudPlaylistManager|MockObject $playlistManagerMock;
    private SoundCloudTrackSearcher|MockObject $trackSearcherMock;
    private LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->playlistManagerMock = $this->createMock(SoundCloudPlaylistManager::class);
        $this->trackSearcherMock = $this->createMock(SoundCloudTrackSearcher::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->soundCloudExportService = new SoundCloudExportService(
            $this->playlistManagerMock,
            $this->trackSearcherMock,
            $this->loggerMock
        );
    }

    public function testIsUserConnectedWithValidProvider(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token')
        ;

        $user = new User()->addProvider($provider);

        $this->assertTrue($this->soundCloudExportService->isUserConnected($user));
    }

    public function testIsUserConnectedWithNoProvider(): void
    {
        $this->assertFalse($this->soundCloudExportService->isUserConnected(new User()));
    }

    public function testIsUserConnectedWithNoAccessToken(): void
    {
        $provider = new Provider()->setName('soundcloud');

        $user = new User()->addProvider($provider);

        $this->assertFalse($this->soundCloudExportService->isUserConnected($user));
    }

    public function testExportPlaylistWithUserNotConnected(): void
    {
        $user = new User();
        $playlist = new Playlist();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User is not connected to SoundCloud');

        $this->soundCloudExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithValidData(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token')
        ;

        $user = new User()->addProvider($provider);

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist();
        $playlist->setName('Test Playlist');
        $playlist->addSong($song);

        $this->playlistManagerMock
            ->expects($this->once())
            ->method('createPlaylist')
            ->with($provider, 'Test Playlist')
            ->willReturn([
                'id' => 123,
                'permalink_url' => 'https://soundcloud.com/playlist/123',
            ])
        ;

        $this->trackSearcherMock
            ->expects($this->once())
            ->method('searchTrack')
            ->with($provider, 'Test Song', 'Test Artist')
            ->willReturn(456)
        ;

        $this->playlistManagerMock
            ->expects($this->once())
            ->method('addTrackToPlaylist')
            ->with($provider, 123, 456)
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertInstanceOf(ExportResult::class, $result);
        $this->assertEquals('123', $result->playlistId);
        $this->assertEquals('https://soundcloud.com/playlist/123', $result->playlistUrl);
        $this->assertEquals(1, $result->exportedTracks);
        $this->assertEquals(0, $result->failedTracks);
        $this->assertEquals('soundcloud', $result->platform);
    }

    public function testExportPlaylistWithSearchTrackNotFound(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token')
        ;

        $user = new User()->addProvider($provider);

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->playlistManagerMock
            ->expects($this->once())
            ->method('createPlaylist')
            ->willReturn([
                'id' => 123,
                'permalink_url' => 'https://soundcloud.com/playlist/123',
            ])
        ;

        $this->trackSearcherMock
            ->expects($this->once())
            ->method('searchTrack')
            ->willReturn(null)
        ;

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with("SoundCloud: No track found for 'Test Song' by 'Test Artist'")
        ;

        $this->playlistManagerMock
            ->expects($this->never())
            ->method('addTrackToPlaylist')
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
    }

    public function testExportPlaylistWithSongWithoutTitleOrArtists(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token')
        ;

        $user = new User()->addProvider($provider);

        $song = new Song();

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->playlistManagerMock
            ->expects($this->once())
            ->method('createPlaylist')
            ->willReturn([
                'id' => 123,
                'permalink_url' => 'https://soundcloud.com/playlist/123',
            ])
        ;

        $this->trackSearcherMock
            ->expects($this->never())
            ->method('searchTrack')
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
    }

    public function testExportPlaylistWithEmptyPlaylist(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->playlistManagerMock
            ->expects($this->once())
            ->method('createPlaylist')
            ->willReturn([
                'id' => 123,
                'permalink_url' => 'https://soundcloud.com/playlist/123',
            ])
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(0, $result->failedTracks);
    }

    public function testExportPlaylistWithAddTrackError(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token')
        ;

        $user = new User()->addProvider($provider);

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->playlistManagerMock
            ->expects($this->once())
            ->method('createPlaylist')
            ->willReturn([
                'id' => 123,
                'permalink_url' => 'https://soundcloud.com/playlist/123',
            ])
        ;

        $this->trackSearcherMock
            ->expects($this->once())
            ->method('searchTrack')
            ->willReturn(456)
        ;

        $this->playlistManagerMock
            ->expects($this->once())
            ->method('addTrackToPlaylist')
            ->willThrowException(new RuntimeException('API Error'))
        ;

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('SoundCloud: Error adding track to playlist - API Error')
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
    }
}
