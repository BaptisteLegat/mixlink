<?php

namespace App\Session\Model\Request;

use OpenApi\Attributes as OA;

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
    public function __construct(
        public string $name,
        public string $playlistName,
        public int $maxParticipants = 50,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPlaylistName(): string
    {
        return $this->playlistName;
    }

    public function getMaxParticipants(): int
    {
        return $this->maxParticipants;
    }
}
