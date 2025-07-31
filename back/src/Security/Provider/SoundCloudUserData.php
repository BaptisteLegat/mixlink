<?php

namespace App\Security\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Override;

class SoundCloudUserData implements ResourceOwnerInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private array $data = [])
    {
    }

    #[Override]
    public function getId(): string
    {
        return (string) $this->data['id'];
    }

    public function getFirstname(): ?string
    {
        /** @var mixed $firstName */
        $firstName = $this->data['first_name'] ?? null;
        return is_string($firstName) ? $firstName : null;
    }

    public function getLastname(): ?string
    {
        /** @var mixed $lastName */
        $lastName = $this->data['last_name'] ?? null;
        return is_string($lastName) ? $lastName : null;
    }

    public function getFullName(): ?string
    {
        /** @var mixed $fullName */
        $fullName = $this->data['full_name'] ?? null;
        return is_string($fullName) ? $fullName : null;
    }

    public function getAvatarUrl(): ?string
    {
        /** @var mixed $avatarUrl */
        $avatarUrl = $this->data['avatar_url'] ?? null;
        return is_string($avatarUrl) ? $avatarUrl : null;
    }

    public function getEmail(): ?string
    {
        /** @var mixed $email */
        $email = $this->data['email'] ?? null;
        return is_string($email) ? $email : null;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(): array
    {
        return $this->data;
    }
}
