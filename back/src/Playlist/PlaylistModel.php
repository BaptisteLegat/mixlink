<?php

namespace App\Playlist;

class PlaylistModel
{
    private string $id = '';

    private ?string $name = null;

    private ?string $sessionCode = null;

    private ?string $userId = null;

    private ?string $createdAt = null;

    private ?string $updatedAt = null;

    private int $songsCount = 0;

    /**
     * @var array<int, \App\Song\SongModel>
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
     * @return array<int, \App\Song\SongModel>
     */
    public function getSongs(): array
    {
        return $this->songs;
    }

    /**
     * @param array<int, \App\Song\SongModel> $songs
     */
    public function setSongs(array $songs): self
    {
        $this->songs = $songs;

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
            'songs' => array_map(fn ($song) => $song->toArray(), $this->songs),
        ];
    }
}
