<?php

namespace App\Song;

use Symfony\Component\Validator\Constraints as Assert;

class SongModel
{
    #[Assert\NotBlank(message: 'Spotify ID is required')]
    private ?string $spotifyId = null;

    #[Assert\NotBlank(message: 'Title is required')]
    private ?string $title = null;

    #[Assert\NotBlank(message: 'Artists is required')]
    private ?string $artists = null;

    private ?string $image = null;

    private ?string $createdAt = null;

    public function getSpotifyId(): ?string
    {
        return $this->spotifyId;
    }

    public function setSpotifyId(?string $spotifyId): self
    {
        $this->spotifyId = $spotifyId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getArtists(): ?string
    {
        return $this->artists;
    }

    public function setArtists(?string $artists): self
    {
        $this->artists = $artists;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'spotifyId' => $this->spotifyId,
            'title' => $this->title,
            'artists' => $this->artists,
            'image' => $this->image,
            'createdAt' => $this->createdAt,
        ];
    }
}
