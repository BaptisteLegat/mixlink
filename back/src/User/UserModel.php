<?php

namespace App\User;

use App\Playlist\PlaylistModel;
use App\Provider\ProviderModel;
use App\Session\Model\SessionModel;
use App\Subscription\SubscriptionModel;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserModel',
    title: 'User Model',
    description: 'Represents a user with providers and subscription',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', description: 'User ID', example: '01234567-89ab-cdef-0123-456789abcdef'),
        new OA\Property(property: 'firstName', type: 'string', nullable: true, description: 'User first name', example: 'John'),
        new OA\Property(property: 'lastName', type: 'string', nullable: true, description: 'User last name', example: 'Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'User email', example: 'john.doe@example.com'),
        new OA\Property(property: 'profilePicture', type: 'string', nullable: true, description: 'Profile picture URL', example: 'https://example.com/profile.jpg'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), description: 'User roles', example: ['ROLE_USER']),
        new OA\Property(property: 'providers', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProviderModel'), description: 'Connected OAuth providers'),
        new OA\Property(property: 'subscription', ref: '#/components/schemas/SubscriptionModel', nullable: true, description: 'User subscription'),
        new OA\Property(property: 'currentSession', ref: '#/components/schemas/SessionModel', nullable: true, description: 'Current active session'),
        new OA\Property(property: 'exportedPlaylists', type: 'array', items: new OA\Items(ref: '#/components/schemas/PlaylistModel'), description: 'User exported playlists'),
    ]
)]
class UserModel
{
    private string $id = '';
    private ?string $firstName = null;
    private ?string $lastName = null;
    private string $email = '';
    private ?string $profilePicture = null;
    /**
     * @var array<array-key, string>
     */
    private array $roles = [];
    /** @var array<ProviderModel> */
    private array $providers = [];
    private ?SubscriptionModel $subscription = null;
    private ?SessionModel $currentSession = null;
    /** @var array<PlaylistModel> */
    private array $exportedPlaylists = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    /**
     * @return array<array-key, string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array<array-key, string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return array<ProviderModel>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * @param array<ProviderModel> $providers
     */
    public function setProviders(array $providers): self
    {
        $this->providers = $providers;

        return $this;
    }

    public function getSubscription(): ?SubscriptionModel
    {
        return $this->subscription;
    }

    public function setSubscription(?SubscriptionModel $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getCurrentSession(): ?SessionModel
    {
        return $this->currentSession;
    }

    public function setCurrentSession(?SessionModel $currentSession): self
    {
        $this->currentSession = $currentSession;

        return $this;
    }

    /**
     * @return array<PlaylistModel>
     */
    public function getExportedPlaylists(): array
    {
        return $this->exportedPlaylists;
    }

    /**
     * @param array<PlaylistModel> $exportedPlaylists
     */
    public function setExportedPlaylists(array $exportedPlaylists): self
    {
        $this->exportedPlaylists = $exportedPlaylists;

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
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'profilePicture' => $this->profilePicture,
            'roles' => $this->roles,
            'providers' => array_map(fn (ProviderModel $provider) => $provider->toArray(), $this->providers),
            'subscription' => $this->subscription ? $this->subscription->toArray() : null,
            'currentSession' => $this->currentSession ? $this->currentSession->toArray() : null,
            'exportedPlaylists' => array_map(fn (PlaylistModel $playlist) => $playlist->toArray(), $this->exportedPlaylists),
        ];
    }
}
