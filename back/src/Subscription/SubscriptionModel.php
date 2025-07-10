<?php

namespace App\Subscription;

use App\Plan\PlanModel;
use DateTimeImmutable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SubscriptionModel',
    title: 'Subscription Model',
    description: 'Represents a user subscription',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', description: 'Subscription ID', example: '123'),
        new OA\Property(property: 'stripeSubscriptionId', type: 'string', nullable: true, description: 'Stripe subscription ID', example: 'sub_1234567890'),
        new OA\Property(property: 'startDate', type: 'string', format: 'date-time', nullable: true, description: 'Subscription start date', example: '2023-01-01T00:00:00+00:00'),
        new OA\Property(property: 'endDate', type: 'string', format: 'date-time', nullable: true, description: 'Subscription end date', example: '2023-12-31T23:59:59+00:00'),
        new OA\Property(property: 'canceledAt', type: 'string', format: 'date-time', nullable: true, description: 'Subscription cancellation date', example: '2023-06-15T10:30:00+00:00'),
        new OA\Property(property: 'status', type: 'string', nullable: true, description: 'Subscription status', example: 'active'),
        new OA\Property(property: 'isActive', type: 'boolean', description: 'Whether the subscription is active', example: true),
        new OA\Property(property: 'isCanceled', type: 'boolean', description: 'Whether the subscription is canceled', example: false),
        new OA\Property(property: 'plan', ref: '#/components/schemas/PlanModel', nullable: true, description: 'Associated plan'),
    ]
)]
class SubscriptionModel
{
    private string $id = '';
    private ?string $stripeSubscriptionId = null;
    private ?DateTimeImmutable $startDate = null;
    private ?DateTimeImmutable $endDate = null;
    private bool $isActive = false;
    private ?PlanModel $plan = null;
    private ?DateTimeImmutable $canceledAt = null;
    private ?string $status = 'active';

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): self
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;

        return $this;
    }

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getCanceledAt(): ?DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function setCanceledAt(?DateTimeImmutable $canceledAt): self
    {
        $this->canceledAt = $canceledAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isCanceled(): bool
    {
        return null !== $this->canceledAt || 'canceled' === $this->status;
    }

    public function getPlan(): ?PlanModel
    {
        return $this->plan;
    }

    public function setPlan(?PlanModel $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    /**
     * Convert the model to an array.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'stripeSubscriptionId' => $this->stripeSubscriptionId,
            'startDate' => $this->startDate ? $this->startDate->format('c') : null,
            'endDate' => $this->endDate ? $this->endDate->format('c') : null,
            'canceledAt' => $this->canceledAt ? $this->canceledAt->format('c') : null,
            'status' => $this->status,
            'isActive' => $this->isActive,
            'isCanceled' => $this->isCanceled(),
            'plan' => $this->plan ? $this->plan->toArray() : null,
        ];
    }
}
