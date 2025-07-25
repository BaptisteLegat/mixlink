<?php

namespace App\Session\Model;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SessionModel',
    title: 'Session Model',
    description: 'Represents a collaborative session',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', description: 'Session ID', example: '01234567-89ab-cdef-0123-456789abcdef'),
        new OA\Property(property: 'name', type: 'string', description: 'Session name', example: 'Ma session collaborative'),
        new OA\Property(property: 'code', type: 'string', description: 'Session code', example: 'ABC12345'),
        new OA\Property(property: 'maxParticipants', type: 'integer', description: 'Maximum participants', example: 50),
        new OA\Property(property: 'host', ref: '#/components/schemas/UserModel', description: 'Session host'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', description: 'Creation date'),
        new OA\Property(property: 'endedAt', type: 'string', format: 'date-time', nullable: true, description: 'End date'),
    ]
)]
class SessionModel
{
    private string $id = '';
    private ?string $name = null;
    private ?string $code = null;
    private ?int $maxParticipants = null;
    /** @var array<string, mixed> */
    private array $host = [];
    private string $createdAt = '';
    private ?string $endedAt = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): self
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHost(): array
    {
        return $this->host;
    }

    /**
     * @param array<string, mixed> $host
     */
    public function setHost(array $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getEndedAt(): ?string
    {
        return $this->endedAt;
    }

    public function setEndedAt(?string $endedAt): self
    {
        $this->endedAt = $endedAt;

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
            'code' => $this->code,
            'maxParticipants' => $this->maxParticipants,
            'host' => $this->host,
            'createdAt' => $this->createdAt,
            'endedAt' => $this->endedAt,
        ];
    }
}
