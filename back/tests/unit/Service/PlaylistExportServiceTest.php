<?php

namespace App\Tests\Unit\Service;

use App\Entity\Playlist;
use App\Entity\User;
use App\Service\Export\ExportServiceFactory;
use App\Service\Export\ExportServiceInterface;
use App\Service\Export\Model\ExportResult;
use App\Service\PlaylistExportService;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class PlaylistExportServiceTest extends TestCase
{
    private PlaylistExportService $playlistExportService;
    private ExportServiceFactory|MockObject $exportServiceFactoryMock;
    private LoggerInterface|MockObject $loggerMock;
    private ExportServiceInterface|MockObject $exportServiceMock;
    private User|MockObject $userMock;
    private Playlist|MockObject $playlistMock;

    protected function setUp(): void
    {
        $this->exportServiceFactoryMock = $this->createMock(ExportServiceFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->exportServiceMock = $this->createMock(ExportServiceInterface::class);
        $this->userMock = $this->createMock(User::class);
        $this->playlistMock = $this->createMock(Playlist::class);

        $this->playlistExportService = new PlaylistExportService(
            $this->exportServiceFactoryMock,
            $this->loggerMock,
        );
    }

    public function testExportPlaylistWithValidPlatform(): void
    {
        $platform = 'spotify';
        $expectedResult = new ExportResult(
            playlistId: 'playlist123',
            playlistUrl: 'https://spotify.com/playlist/123',
            exportedTracks: 5,
            failedTracks: 1,
            platform: 'spotify',
        );


        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('isSupported')
            ->with($platform)
            ->willReturn(true)
        ;

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with($platform)
            ->willReturn($this->exportServiceMock)
        ;

        $this->exportServiceMock
            ->expects($this->once())
            ->method('isUserConnected')
            ->with($this->userMock)
            ->willReturn(true)
        ;

        $this->exportServiceMock
            ->expects($this->once())
            ->method('exportPlaylist')
            ->with($this->playlistMock, $this->userMock)
            ->willReturn($expectedResult)
        ;

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
        ;

        $result = $this->playlistExportService->exportPlaylist($this->playlistMock, $this->userMock, $platform);

        $this->assertInstanceOf(ExportResult::class, $result);
        $this->assertEquals('playlist123', $result->playlistId);
        $this->assertEquals('https://spotify.com/playlist/123', $result->playlistUrl);
        $this->assertEquals(5, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('spotify', $result->platform);
    }

    public function testExportPlaylistWithUnsupportedPlatform(): void
    {
        $platform = 'unsupported';

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('isSupported')
            ->with($platform)
            ->willReturn(false)
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Platform 'unsupported' is not supported");

        $this->playlistExportService->exportPlaylist($this->playlistMock, $this->userMock, $platform);
    }

    public function testExportPlaylistWithUserNotConnected(): void
    {
        $platform = 'spotify';

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('isSupported')
            ->with($platform)
            ->willReturn(true)
        ;

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with($platform)
            ->willReturn($this->exportServiceMock)
        ;

        $this->exportServiceMock
            ->expects($this->once())
            ->method('isUserConnected')
            ->with($this->userMock)
            ->willReturn(false)
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User is not connected to spotify');

        $this->playlistExportService->exportPlaylist($this->playlistMock, $this->userMock, $platform);
    }

    public function testExportPlaylistWithException(): void
    {
        $platform = 'spotify';

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('isSupported')
            ->with($platform)
            ->willReturn(true)
        ;

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with($platform)
            ->willReturn($this->exportServiceMock)
        ;

        $this->exportServiceMock
            ->expects($this->once())
            ->method('isUserConnected')
            ->with($this->userMock)
            ->willReturn(true)
        ;

        $exceptionMessage = 'An error occurred during export';
        $this->exportServiceMock
            ->expects($this->once())
            ->method('exportPlaylist')
            ->with($this->playlistMock, $this->userMock)
            ->willThrowException(new InvalidArgumentException($exceptionMessage))
        ;

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to export playlist',
                [
                    'exception' => $exceptionMessage,
                    'platform' => $platform,
                ]
            )
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to export playlist: '.$exceptionMessage);

        $this->playlistExportService->exportPlaylist($this->playlistMock, $this->userMock, $platform);
    }

    public function testGetSupportedPlatforms(): void
    {
        $expectedPlatforms = ['spotify', 'google', 'soundcloud'];

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('getAllServices')
            ->willReturn(array_flip($expectedPlatforms))
        ;

        $result = $this->playlistExportService->getSupportedPlatforms();

        $this->assertEquals($expectedPlatforms, $result);
    }
}
