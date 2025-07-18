<?php

namespace App\Entity;

use App\Interface\BlameableInterface;
use App\Interface\TimestampableInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'session')]
class Session implements BlameableInterface, TimestampableInterface
{
    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 8, unique: true, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $host;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $endedAt = null;

    /**
     * @var Collection|SessionParticipant[]
     */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: SessionParticipant::class)]
    private Collection $participants;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getHost(): User
    {
        return $this->host;
    }

    public function setHost(User $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getEndedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?DateTimeImmutable $endedAt): self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    /**
     * @return Collection|SessionParticipant[]
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }
}
