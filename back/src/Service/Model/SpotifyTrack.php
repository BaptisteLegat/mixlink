<?php

namespace App\Service\Model;

class SpotifyTrack
{
    /**
     * @param string[] $artists
     */
    public function __construct(
        private string $id,
        private string $name,
        private array $artists,
        private ?string $image = null,
        private ?string $previewUrl = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getArtists(): array
    {
        return $this->artists;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getPreviewUrl(): ?string
    {
        return $this->previewUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'artists' => $this->artists,
            'image' => $this->image,
            'preview_url' => $this->previewUrl,
        ];
    }
}
