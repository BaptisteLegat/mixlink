<?php

namespace App\Entity;

use App\Entity\Embeddable\Money;
use App\Interface\BlameableInterface;
use App\Interface\TimestampableInterface;
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
#[ORM\Table(name: 'plan')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: false)]
class Plan implements BlameableInterface, TimestampableInterface
{
    use BlameableEntity;
    use TimestampableEntity;
    use SoftDeleteableEntity;

    public const string FREE = 'free';
    public const string PREMIUM = 'premium';
    public const string CUSTOME_MADE = 'custom';
    public const PLANS = [
        self::FREE => 'Free',
        self::PREMIUM => 'Premium',
        self::CUSTOME_MADE => 'Custom',
    ];

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private $isCustom = false;

    #[ORM\Embedded(class: Money::class)]
    private Money $price;

    /**
     * @var Collection<int, Subscription>
     */
    #[ORM\OneToMany(mappedBy: 'plan', targetEntity: Subscription::class)]
    private Collection $subscriptions;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $stripePriceId = null;

    public function __construct()
    {
        $this->price = new Money();
        $this->subscriptions = new ArrayCollection();
    }

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

    public function isCustom(): bool
    {
        return $this->isCustom;
    }

    public function setCustom(bool $isCustom): self
    {
        $this->isCustom = $isCustom;

        return $this;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function setPrice(Money $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): self
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions[] = $subscription;
            $subscription->setPlan($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): self
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getPlan() === $this) {
                $subscription->setPlan(null);
            }
        }

        return $this;
    }

    public function getStripePriceId(): ?string
    {
        return $this->stripePriceId;
    }

    public function setStripePriceId(?string $stripePriceId): self
    {
        $this->stripePriceId = $stripePriceId;

        return $this;
    }
}
