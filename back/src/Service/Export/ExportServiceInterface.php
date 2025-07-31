<?php

namespace App\Service\Export;

use App\Entity\Playlist;
use App\Entity\User;

interface ExportServiceInterface
{
    /**
     * Export a playlist to the platform.
     *
     * @param Playlist $playlist The playlist to export
     * @param User     $user     The user who owns the playlist
     *
     * @return array{playlist_id: string, playlist_url: string, exported_tracks: int, failed_tracks: int}
     */
    public function exportPlaylist(Playlist $playlist, User $user): array;

    /**
     * Get the platform name.
     */
    public function getPlatformName(): string;

    /**
     * Check if the user is connected to this platform.
     */
    public function isUserConnected(User $user): bool;
}
