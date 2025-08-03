<?php

namespace App\Service\Export\Model;

use JsonSerializable;
use Override;

readonly class ExportResult implements JsonSerializable
{
    public function __construct(
        public string $playlistId,
        public string $playlistUrl,
        public int $exportedTracks,
        public int $failedTracks,
        public string $platform,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'playlist_id' => $this->playlistId,
            'playlist_url' => $this->playlistUrl,
            'exported_tracks' => $this->exportedTracks,
            'failed_tracks' => $this->failedTracks,
            'platform' => $this->platform,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    public function hasFailures(): bool
    {
        return $this->failedTracks > 0;
    }

    public function isFullSuccess(): bool
    {
        return 0 === $this->failedTracks && $this->exportedTracks > 0;
    }

    public function isEmpty(): bool
    {
        return 0 === $this->exportedTracks && 0 === $this->failedTracks;
    }

    public function getTotalTracksProcessed(): int
    {
        return $this->exportedTracks + $this->failedTracks;
    }

    public function getSuccessRate(): float
    {
        $total = $this->getTotalTracksProcessed();
        if (0 === $total) {
            return 0.0;
        }

        return ((float) $this->exportedTracks / (float) $total) * 100.0;
    }
}
