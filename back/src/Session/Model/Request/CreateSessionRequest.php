<?php

namespace App\Session\Model\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CreateSessionRequest',
    title: 'Create Session Request',
    description: 'Request to create a new session',
    type: 'object',
    required: ['name', 'playlistName'],
    properties: [
        new OA\Property(property: 'name', type: 'string', description: 'Session name', example: 'Ma session collaborative'),
        new OA\Property(property: 'playlistName', type: 'string', description: 'Playlist name', example: 'Ma playlist collaborative'),
        new OA\Property(property: 'maxParticipants', type: 'integer', description: 'Maximum participants', example: 50, minimum: 1, maximum: 100),
    ]
)]
class CreateSessionRequest
{
    #[Assert\NotBlank(message: 'session.create.errors.name_required')]
    public string $name = '';

    #[Assert\NotBlank(message: 'session.create.errors.playlist_name_required')]
    public string $playlistName = '';

    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'session.create.errors.max_participants_invalid')]
    public int $maxParticipants = 3;

    public function __construct(string $name = '', string $playlistName = '', int $maxParticipants = 3)
    {
        $this->name = $name;
        $this->playlistName = $playlistName;
        $this->maxParticipants = $maxParticipants;
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

    public function getPlaylistName(): string
    {
        return $this->playlistName;
    }

    public function setPlaylistName(string $playlistName): self
    {
        $this->playlistName = $playlistName;

        return $this;
    }

    public function getMaxParticipants(): int
    {
        return $this->maxParticipants;
    }
}
