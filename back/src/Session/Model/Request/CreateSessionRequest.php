<?php

namespace App\Session\Model\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateSessionRequest',
    title: 'Create Session Request',
    description: 'Request to create a new session',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', description: 'Session name', example: 'Ma session collaborative'),
        new OA\Property(property: 'maxParticipants', type: 'integer', description: 'Maximum participants', example: 50, minimum: 1, maximum: 100),
    ]
)]
class CreateSessionRequest
{
    public function __construct(
        public readonly string $name,
        public readonly int $maxParticipants = 50,
    ) {
    }
}
