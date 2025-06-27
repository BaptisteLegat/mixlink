<?php

namespace App\Entity\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Money
{
    public const int AMOUNT_DIVIDER = 100;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $amount;

    #[ORM\Column]
    private string $currency;

    public function __construct(?int $amountInCents = 0, string $currency = 'EUR')
    {
        $this->amount = $amountInCents;
        $this->currency = $currency;
    }

    public function __toString(): string
    {
        return ($this->amount / self::AMOUNT_DIVIDER).' '.$this->currency;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
