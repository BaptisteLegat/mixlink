<?php

namespace App\User;

use App\Provider\ProviderModel;
use App\Subscription\SubscriptionModel;

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
        ];
    }
}
