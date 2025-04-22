<?php

namespace App\Subscription;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Trait\TraceableTrait;
use DateTimeImmutable;

class SubscriptionManager
{
    use TraceableTrait;

    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionMapper $subscriptionMapper,
    ) {
    }

    public function create(User $user, Plan $plan, string $stripeSubscriptionId): Subscription
    {
        $existingSubscription = $user->getSubscription();

        $subscription = $this->subscriptionMapper->mapEntity(
            $user,
            $plan,
            $stripeSubscriptionId,
            new DateTimeImmutable(),
            (new DateTimeImmutable())->modify('+1 month'),
            $existingSubscription
        );

        $isUpdate = $existingSubscription instanceof Subscription;

        $this->setTimestampable($subscription, $isUpdate);
        $this->setBlameable($subscription, $user->getEmail(), $isUpdate);

        $this->subscriptionRepository->save($subscription, true);

        return $subscription;
    }
}
