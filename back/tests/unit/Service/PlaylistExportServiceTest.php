<?php

namespace App\Tests\Unit\Service;

use App\Entity\Plan;
use App\Entity\Playlist;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\PlaylistRepository;
use App\Service\Export\ExportServiceFactory;
use App\Service\Export\ExportServiceInterface;
use App\Service\Export\Model\ExportResult;
use App\Service\PlaylistExportService;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PlaylistExportServiceTest extends TestCase
{
    private PlaylistExportService $playlistExportService;
    private ExportServiceFactory|MockObject $exportServiceFactoryMock;
    private LoggerInterface|MockObject $loggerMock;
    private ExportServiceInterface|MockObject $exportServiceMock;
    private PlaylistRepository|MockObject $playlistRepositoryMock;

    protected function setUp(): void
    {
        $this->exportServiceFactoryMock = $this->createMock(ExportServiceFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->exportServiceMock = $this->createMock(ExportServiceInterface::class);
        $this->playlistRepositoryMock = $this->createMock(PlaylistRepository::class);

        $this->playlistExportService = new PlaylistExportService(
            $this->exportServiceFactoryMock,
            $this->loggerMock,
            $this->playlistRepositoryMock
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
            ->with(new User())
            ->willReturn(true)
        ;

        $this->exportServiceMock
            ->expects($this->once())
            ->method('exportPlaylist')
            ->with(new Playlist(), new User())
            ->willReturn($expectedResult)
        ;

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
        ;

        $result = $this->playlistExportService->exportPlaylist(new Playlist(), new User(), $platform);

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

        $this->playlistExportService->exportPlaylist(new Playlist(), new User(), $platform);
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
            ->with(new User())
            ->willReturn(false)
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User is not connected to spotify');

        $this->playlistExportService->exportPlaylist(new Playlist(), new User(), $platform);
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
            ->with(new User())
            ->willReturn(true)
        ;

        $exceptionMessage = 'An error occurred during export';
        $this->exportServiceMock
            ->expects($this->once())
            ->method('exportPlaylist')
            ->with(new Playlist(), new User())
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

        $this->playlistExportService->exportPlaylist(new Playlist(), new User(), $platform);
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

    public function testRollbackExportWithPremium(): void
    {
        $plan = new Plan()->setName(Plan::PREMIUM);
        $subscription = new Subscription()->setPlan($plan);
        $user = new User()->setSubscription($subscription);

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->setUser($user)
        ;

        $this->playlistRepositoryMock
            ->expects($this->never())
            ->method('save')
        ;

        $this->playlistExportService->rollbackExport($playlist, $user);
    }

    public function testRollbackExportWithFreePlan(): void
    {
        $plan = new Plan()->setName(Plan::FREE);
        $subscription = new Subscription()->setPlan($plan);
        $user = new User()->setSubscription($subscription);

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->setUser($user)
            ->setHasBeenExported(true)
            ->setExportedPlaylistId('abc123')
            ->setExportedPlaylistUrl('http://urltest')
        ;

        $this->playlistRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($playlist, true)
        ;

        $this->playlistExportService->rollbackExport($playlist, $user);
    }
}
