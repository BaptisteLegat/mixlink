<?php

namespace App\Service\Export;

use App\Entity\Playlist;
use App\Entity\User;

interface ExportServiceInterface
{
    /**
     * @return array{playlist_id: string, playlist_url: string, exported_tracks: int, failed_tracks: int}
     */
    public function exportPlaylist(Playlist $playlist, User $user): array;

    public function getPlatformName(): string;

    public function isUserConnected(User $user): bool;
}
