<?php

namespace App\Mail;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ContactModel',
    title: 'Contact Model',
    description: 'Represents a contact form submission',
    type: 'object',
    required: ['name', 'email', 'subject', 'message'],
    properties: [
        new OA\Property(property: 'name', type: 'string', description: 'Contact name', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Contact email', example: 'john.doe@example.com'),
        new OA\Property(property: 'subject', type: 'string', description: 'Message subject', example: 'Product inquiry'),
        new OA\Property(property: 'message', type: 'string', description: 'Message content', example: 'Hello, I would like to know more about...'),
    ]
)]
class ContactModel
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $subject,
        public readonly string $message,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
