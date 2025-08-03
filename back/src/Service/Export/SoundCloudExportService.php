<?php

namespace App\Service\Export;

use App\ApiResource\ApiReference;
use App\Entity\Playlist;
use App\Entity\Provider;
use App\Entity\Song;
use App\Entity\User;
use App\Service\Export\Model\ExportResult;
use App\Service\Export\SoundCloud\SoundCloudPlaylistManager;
use App\Service\Export\SoundCloud\SoundCloudTrackSearcher;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SoundCloudExportService implements ExportServiceInterface
{
    public function __construct(
        private SoundCloudPlaylistManager $playlistManager,
        private SoundCloudTrackSearcher $trackSearcher,
        private LoggerInterface $logger,
    ) {
    }

    #[Override]
    public function exportPlaylist(Playlist $playlist, User $user): ExportResult
    {
        $provider = $user->getProviderByName('soundcloud');
        if (null === $provider) {
            throw new InvalidArgumentException('User is not connected to SoundCloud');
        }

        $playlistData = $this->playlistManager->createPlaylist($provider, $playlist->getName() ?? 'mixlink Playlist');

        $playlistId = $playlistData['id'];
        $playlistUrl = $playlistData['permalink_url'];

        $exportResult = $this->addTracksToPlaylist($provider, $playlistId, $playlist->getSongs());

        return new ExportResult(
            playlistId: (string) $playlistId,
            playlistUrl: $playlistUrl,
            exportedTracks: $exportResult['exported_tracks'],
            failedTracks: $exportResult['failed_tracks'],
            platform: ApiReference::SOUNDCLOUD,
        );
    }

    #[Override]
    public function isUserConnected(User $user): bool
    {
        $provider = $user->getProviderByName(ApiReference::SOUNDCLOUD);

        return null !== $provider && null !== $provider->getAccessToken();
    }

    /**
     * @param Collection<int, Song> $songs
     *
     * @return array{exported_tracks: int, failed_tracks: int}
     */
    private function addTracksToPlaylist(Provider $provider, int $playlistId, Collection $songs): array
    {
        $exportedTracks = 0;
        $failedTracks = 0;

        foreach ($songs as $song) {
            try {
                $title = $song->getTitle();
                $artists = $song->getArtists();

                if (null === $title || null === $artists) {
                    ++$failedTracks;
                    continue;
                }

                $trackId = $this->trackSearcher->searchTrack($provider, $title, $artists);

                if (null === $trackId) {
                    $this->logger->warning("SoundCloud: No track found for '$title' by '$artists'");
                    ++$failedTracks;
                    continue;
                }

                $this->logger->info("SoundCloud: Found track ID $trackId for '$title' by '$artists'");
                $this->playlistManager->addTrackToPlaylist($provider, $playlistId, $trackId);
                ++$exportedTracks;
            } catch (RuntimeException $e) {
                $this->logger->error('SoundCloud: Error adding track to playlist - '.$e->getMessage());
                ++$failedTracks;
            }
        }

        return [
            'exported_tracks' => $exportedTracks,
            'failed_tracks' => $failedTracks,
        ];
    }
}
