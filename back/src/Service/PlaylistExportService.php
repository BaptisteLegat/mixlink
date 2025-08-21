<?php

namespace App\Service;

use App\Entity\Plan;
use App\Entity\Playlist;
use App\Entity\User;
use App\Repository\PlaylistRepository;
use App\Service\Export\ExportServiceFactory;
use App\Service\Export\Model\ExportResult;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PlaylistExportService
{
    public function __construct(
        #[Autowire(service: ExportServiceFactory::class)]
        private ExportServiceFactory $exportServiceFactory,
        private LoggerInterface $logger,
        private PlaylistRepository $playlistRepository,
    ) {
    }

    public function exportPlaylist(Playlist $playlist, User $user, string $platform): ExportResult
    {
        if (!$this->exportServiceFactory->isSupported($platform)) {
            throw new InvalidArgumentException("Platform '$platform' is not supported");
        }

        $exportService = $this->exportServiceFactory->create($platform);

        if (!$exportService->isUserConnected($user)) {
            throw new InvalidArgumentException("User is not connected to $platform");
        }

        try {
            $result = $exportService->exportPlaylist($playlist, $user);
            if ($result->hasFailures()) {
                $this->logger->warning('Playlist export completed with failures', array_merge(
                    $result->jsonSerialize(),
                    ['platform' => $platform]
                ));
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Failed to export playlist', [
                'exception' => $e->getMessage(),
                'platform' => $platform,
            ]);

            throw new InvalidArgumentException('Failed to export playlist: '.$e->getMessage());
        }
    }

    /**
     * @return array<string>
     */
    public function getSupportedPlatforms(): array
    {
        return array_keys($this->exportServiceFactory->getAllServices());
    }

    public function rollbackExport(Playlist $playlist, User $user): void
    {
        $subscription = $user->getSubscription();
        $isFreePlan = Plan::FREE === $subscription?->getPlan()?->getName();

        if ($isFreePlan) {
            $playlist->setHasBeenExported(false);
            $playlist->setExportedPlaylistId(null);
            $playlist->setExportedPlaylistUrl(null);

            $this->playlistRepository->save($playlist, true);
        }
    }
}
