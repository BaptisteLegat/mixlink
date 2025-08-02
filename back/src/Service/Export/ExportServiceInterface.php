<?php

namespace App\Service\Export;

use App\Entity\Playlist;
use App\Entity\User;
use App\Service\Export\Model\ExportResult;

interface ExportServiceInterface
{
    public function exportPlaylist(Playlist $playlist, User $user): ExportResult;

    public function getPlatformName(): string;

    public function isUserConnected(User $user): bool;
}
