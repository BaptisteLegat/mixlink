<?php

namespace App\Provider;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProviderModel',
    title: 'Provider Model',
    description: 'Represents an OAuth provider connection',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', description: 'Provider ID', example: '01234567-89ab-cdef-0123-456789abcdef'),
        new OA\Property(property: 'name', type: 'string', description: 'Provider name', example: 'google'),
        new OA\Property(property: 'isMain', type: 'boolean', description: 'Whether this is the main provider for authentication', example: true),
    ]
)]
class ProviderModel
{
    private string $id = '';
    private string $name = '';
    private ?string $accessToken = null;
    private ?string $refreshToken = null;
    private bool $isMain = false;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function isMain(): bool
    {
        return $this->isMain;
    }

    public function setIsMain(bool $isMain): self
    {
        $this->isMain = $isMain;

        return $this;
    }

    /**
     * Convert the model to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isMain' => $this->isMain,
        ];
    }
}
