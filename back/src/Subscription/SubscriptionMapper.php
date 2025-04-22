<?php

namespace App\Subscription;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use DateTimeImmutable;

class SubscriptionMapper
{
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
}
