<?php

namespace App\Playlist;

use App\Song\SongModel;

class PlaylistModel
{
    private string $id = '';

    private ?string $name = null;

    private ?string $sessionCode = null;

    private ?string $userId = null;

    private ?string $createdAt = null;

    private ?string $updatedAt = null;

    private int $songsCount = 0;

    private bool $hasBeenExported = false;

    private ?string $exportedPlaylistId = null;

    private ?string $exportedPlaylistUrl = null;

    /**
     * @var array<int, SongModel>
     */
    private array $songs = [];

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSessionCode(): ?string
    {
        return $this->sessionCode;
    }

    public function setSessionCode(?string $sessionCode): self
    {
        $this->sessionCode = $sessionCode;

        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSongsCount(): int
    {
        return $this->songsCount;
    }

    public function setSongsCount(int $songsCount): self
    {
        $this->songsCount = $songsCount;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return array<int, SongModel>
     */
    public function getSongs(): array
    {
        return $this->songs;
    }

    /**
     * @param array<int, SongModel> $songs
     */
    public function setSongs(array $songs): self
    {
        $this->songs = $songs;

        return $this;
    }

    public function hasBeenExported(): bool
    {
        return $this->hasBeenExported;
    }

    public function setHasBeenExported(bool $hasBeenExported): self
    {
        $this->hasBeenExported = $hasBeenExported;

        return $this;
    }

    public function getExportedPlaylistId(): ?string
    {
        return $this->exportedPlaylistId;
    }

    public function setExportedPlaylistId(?string $exportedPlaylistId): self
    {
        $this->exportedPlaylistId = $exportedPlaylistId;

        return $this;
    }

    public function getExportedPlaylistUrl(): ?string
    {
        return $this->exportedPlaylistUrl;
    }

    public function setExportedPlaylistUrl(?string $exportedPlaylistUrl): self
    {
        $this->exportedPlaylistUrl = $exportedPlaylistUrl;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sessionCode' => $this->sessionCode,
            'userId' => $this->userId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'songsCount' => $this->songsCount,
            'hasBeenExported' => $this->hasBeenExported,
            'exportedPlaylistId' => $this->exportedPlaylistId,
            'exportedPlaylistUrl' => $this->exportedPlaylistUrl,
            'songs' => array_map(fn ($song) => $song->toArray(), $this->songs),
        ];
    }
}
