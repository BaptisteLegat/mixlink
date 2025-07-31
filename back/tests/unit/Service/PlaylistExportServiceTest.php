<?php

namespace App\Tests\Unit\Service;

use App\Entity\Playlist;
use App\Entity\User;
use App\Provider\ProviderManager;
use App\Service\Export\ExportServiceFactory;
use App\Service\Export\ExportServiceInterface;
use App\Service\PlaylistExportService;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaylistExportServiceTest extends TestCase
{
    private PlaylistExportService $playlistExportService;
    private ExportServiceFactory|MockObject $exportServiceFactoryMock;
    private ProviderManager|MockObject $providerManagerMock;
    private ExportServiceInterface|MockObject $exportServiceMock;
    private User|MockObject $userMock;
    private Playlist|MockObject $playlistMock;

    protected function setUp(): void
    {
        $this->exportServiceFactoryMock = $this->createMock(ExportServiceFactory::class);
        $this->providerManagerMock = $this->createMock(ProviderManager::class);
        $this->exportServiceMock = $this->createMock(ExportServiceInterface::class);
        $this->userMock = $this->createMock(User::class);
        $this->playlistMock = $this->createMock(Playlist::class);

        $this->playlistExportService = new PlaylistExportService(
            $this->exportServiceFactoryMock,
            $this->providerManagerMock,
        );
    }

    public function testExportPlaylistWithValidPlatform(): void
    {
        $platform = 'spotify';
        $expectedResult = [
            'playlist_id' => 'playlist123',
            'playlist_url' => 'https://spotify.com/playlist/123',
            'exported_tracks' => 5,
            'failed_tracks' => 1,
        ];

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('isSupported')
            ->with($platform)
            ->willReturn(true);

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with($platform)
            ->willReturn($this->exportServiceMock);

        $this->exportServiceMock
            ->expects($this->once())
            ->method('isUserConnected')
            ->with($this->userMock)
            ->willReturn(true);

        $this->exportServiceMock
            ->expects($this->once())
            ->method('exportPlaylist')
            ->with($this->playlistMock, $this->userMock)
            ->willReturn($expectedResult);

        $result = $this->playlistExportService->exportPlaylist($this->playlistMock, $this->userMock, $platform);

        $expectedResultWithPlatform = array_merge($expectedResult, ['platform' => $platform]);
        $this->assertEquals($expectedResultWithPlatform, $result);
    }

    public function testExportPlaylistWithUnsupportedPlatform(): void
    {
        $platform = 'unsupported';

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('isSupported')
            ->with($platform)
            ->willReturn(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Platform '$platform' is not supported");

        $this->playlistExportService->exportPlaylist($this->playlistMock, $this->userMock, $platform);
    }

    public function testExportPlaylistWithUserNotConnected(): void
    {
        $platform = 'spotify';

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('isSupported')
            ->with($platform)
            ->willReturn(true);

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with($platform)
            ->willReturn($this->exportServiceMock);

        $this->exportServiceMock
            ->expects($this->once())
            ->method('isUserConnected')
            ->with($this->userMock)
            ->willReturn(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("User is not connected to $platform");

        $this->playlistExportService->exportPlaylist($this->playlistMock, $this->userMock, $platform);
    }

    public function testGetAvailablePlatforms(): void
    {
        $services = [
            'spotify' => $this->createMock(ExportServiceInterface::class),
            'google' => $this->createMock(ExportServiceInterface::class),
            'soundcloud' => $this->createMock(ExportServiceInterface::class),
        ];

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('getAllServices')
            ->willReturn($services);

        $services['spotify']
            ->expects($this->once())
            ->method('isUserConnected')
            ->with($this->userMock)
            ->willReturn(true);

        $services['google']
            ->expects($this->once())
            ->method('isUserConnected')
            ->with($this->userMock)
            ->willReturn(false);

        $services['soundcloud']
            ->expects($this->once())
            ->method('isUserConnected')
            ->with($this->userMock)
            ->willReturn(true);

        $result = $this->playlistExportService->getAvailablePlatforms($this->userMock);

        $this->assertEquals(['spotify', 'soundcloud'], $result);
    }

    public function testGetSupportedPlatforms(): void
    {
        $expectedPlatforms = ['spotify', 'google', 'soundcloud'];

        $this->exportServiceFactoryMock
            ->expects($this->once())
            ->method('getAllServices')
            ->willReturn(array_flip($expectedPlatforms));

        $result = $this->playlistExportService->getSupportedPlatforms();

        $this->assertEquals($expectedPlatforms, $result);
    }
}
