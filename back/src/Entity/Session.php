<?php

namespace App\Entity;

use App\Interface\BlameableInterface;
use App\Interface\TimestampableInterface;
use DateTimeImmutable;
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

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 8, unique: true)]
    private string $code;

    #[ORM\Column(type: 'integer')]
    private int $maxParticipants = 50;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $host;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $endedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }


    public function getMaxParticipants(): int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): self
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
}
