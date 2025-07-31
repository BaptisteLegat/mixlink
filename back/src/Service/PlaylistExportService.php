<?php

namespace App\Service;

use App\Entity\Playlist;
use App\Entity\User;
use App\Service\Export\ExportServiceFactory;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PlaylistExportService
{
    public function __construct(
        #[Autowire(service: ExportServiceFactory::class)]
        private ExportServiceFactory $exportServiceFactory,
    ) {
    }

    /**
     * Export a playlist to the specified platform.
     *
     * @return array{playlist_id: string, playlist_url: string, exported_tracks: int, failed_tracks: int, platform: string}
     */
    public function exportPlaylist(Playlist $playlist, User $user, string $platform): array
    {
        if (!$this->exportServiceFactory->isSupported($platform)) {
            throw new InvalidArgumentException("Platform '$platform' is not supported");
        }

        $exportService = $this->exportServiceFactory->create($platform);

        if (!$exportService->isUserConnected($user)) {
            throw new InvalidArgumentException("User is not connected to $platform");
        }

        $exportResult = $exportService->exportPlaylist($playlist, $user);

        return array_merge($exportResult, ['platform' => $platform]);
    }

    /**
     * @return array<string>
     */
    public function getSupportedPlatforms(): array
    {
        return array_keys($this->exportServiceFactory->getAllServices());
    }
}
