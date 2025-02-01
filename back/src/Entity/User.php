<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class User
{
    use BlameableEntity;
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'integer')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    /**
     * @var Collection|Provider[]
     */
    #[ORM\OneToMany(targetEntity: Provider::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $providers;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deletedBy = null;

    public function __construct()
    {
        $this->providers = new ArrayCollection();
    }

    // Getters and setters

    public function addProvider(Provider $provider): self
    {
        if (!$this->providers->contains($provider)) {
            $this->providers[] = $provider;
            $provider->setUser($this);
        }

        return $this;
    }

    public function removeProvider(Provider $provider): self
    {
        if ($this->providers->removeElement($provider)) {
            // set the owning side to null (unless already changed)
            if ($provider->getUser() === $this) {
                $provider->setUser(null);
            }
        }

        return $this;
    }

    public function getProviders(): Collection
    {
        return $this->providers;
    }
}
