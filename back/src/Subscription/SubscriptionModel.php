<?php

namespace App\Subscription;

use App\Plan\PlanModel;
use DateTimeImmutable;

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
