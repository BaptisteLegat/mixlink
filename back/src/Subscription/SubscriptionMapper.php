<?php

namespace App\Subscription;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Plan\PlanMapper;
use DateTimeImmutable;

class SubscriptionMapper
{
    public function __construct(
        private PlanMapper $planMapper,
    ) {
    }

    public function mapEntity(
        User $user,
        Plan $plan,
        string $stripeSubscriptionId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?Subscription $existingSubscription = null,
    ): Subscription {
        $subscription = $existingSubscription ?? new Subscription();

        return $subscription
            ->setUser($user)
            ->setPlan($plan)
            ->setStripeSubscriptionId($stripeSubscriptionId)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setCanceledAt(null)
            ->setStatus('active')
        ;
    }

    public function mapModel(Subscription $subscription): SubscriptionModel
    {
        $subscriptionModel = (new SubscriptionModel())
            ->setId((string) $subscription->getId())
            ->setStripeSubscriptionId($subscription->getStripeSubscriptionId())
            ->setStartDate($subscription->getStartDate())
            ->setEndDate($subscription->getEndDate())
            ->setCanceledAt($subscription->getCanceledAt())
            ->setStatus($subscription->getStatus())
            ->setIsActive($subscription->isActive())
        ;

        $plan = $subscription->getPlan();
        if (null !== $plan) {
            $subscriptionModel->setPlan($this->planMapper->mapModel($plan));
        }

        return $subscriptionModel;
    }
}
