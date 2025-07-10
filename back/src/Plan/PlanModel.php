<?php

namespace App\Plan;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PlanModel',
    title: 'Plan Model',
    description: 'Represents a subscription plan',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', description: 'Plan ID', example: '01234567-89ab-cdef-0123-456789abcdef'),
        new OA\Property(property: 'name', type: 'string', description: 'Plan name', example: 'premium'),
        new OA\Property(property: 'price', type: 'number', format: 'float', description: 'Plan price', example: 9.99),
        new OA\Property(property: 'currency', type: 'string', description: 'Currency code', example: 'EUR'),
        new OA\Property(property: 'stripePriceId', type: 'string', nullable: true, description: 'Stripe price ID', example: 'price_1234567890'),
    ]
)]
class PlanModel
{
    private string $id = '';
    private string $name = '';
    private float $price = 0.0;
    private string $currency = 'EUR';
    private ?string $stripePriceId = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
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

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

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

    /**
     * Convert the model to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'currency' => $this->currency,
            'stripePriceId' => $this->stripePriceId,
        ];
    }
}
