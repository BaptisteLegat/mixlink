<?php

namespace App\Service\Export;

use App\ApiResource\ApiReference;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ExportServiceFactory
{
    public function __construct(
        #[Autowire(service: SpotifyExportService::class)]
        private SpotifyExportService $spotifyExportService,
        #[Autowire(service: GoogleExportService::class)]
        private GoogleExportService $googleExportService,
        #[Autowire(service: SoundCloudExportService::class)]
        private SoundCloudExportService $soundCloudExportService,
    ) {
    }

    public function create(string $platform): ExportServiceInterface
    {
        return match ($platform) {
            ApiReference::SPOTIFY => $this->spotifyExportService,
            ApiReference::GOOGLE => $this->googleExportService,
            ApiReference::SOUNDCLOUD => $this->soundCloudExportService,
            default => throw new InvalidArgumentException("Platform '$platform' is not supported"),
        };
    }

    /**
     * @return array<string, ExportServiceInterface>
     */
    public function getAllServices(): array
    {
        return [
            ApiReference::SPOTIFY => $this->spotifyExportService,
            ApiReference::GOOGLE => $this->googleExportService,
            ApiReference::SOUNDCLOUD => $this->soundCloudExportService,
        ];
    }

    public function isSupported(string $platform): bool
    {
        return in_array($platform, [ApiReference::SPOTIFY, ApiReference::GOOGLE, ApiReference::SOUNDCLOUD], true);
    }
}
