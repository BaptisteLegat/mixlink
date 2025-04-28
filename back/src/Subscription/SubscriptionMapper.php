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

        $subscription->setUser($user);
        $subscription->setPlan($plan);
        $subscription->setStripeSubscriptionId($stripeSubscriptionId);
        $subscription->setStartDate($startDate);
        $subscription->setEndDate($endDate);

        return $subscription;
    }

    public function mapModel(Subscription $subscription): SubscriptionModel
    {
        $subscriptionModel = new SubscriptionModel();

        $subscriptionModel
            ->setId((string) $subscription->getId())
            ->setStripeSubscriptionId($subscription->getStripeSubscriptionId())
            ->setStartDate($subscription->getStartDate())
            ->setEndDate($subscription->getEndDate())
            ->setIsActive($subscription->isActive())
        ;

        $plan = $subscription->getPlan();
        if (null !== $plan) {
            $subscriptionModel->setPlan($this->planMapper->mapModel($plan));
        }

        return $subscriptionModel;
    }
}
