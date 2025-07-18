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
#[ORM\Table(name: 'session_participant')]
class SessionParticipant implements BlameableInterface, TimestampableInterface
{
    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Session $session;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $pseudo = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $leftAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function setSession(Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getLeftAt(): ?DateTimeImmutable
    {
        return $this->leftAt;
    }

    public function setLeftAt(?DateTimeImmutable $leftAt): self
    {
        $this->leftAt = $leftAt;

        return $this;
    }
}
