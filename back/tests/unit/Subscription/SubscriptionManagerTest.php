<?php

namespace App\Tests\Unit\Subscription;

use App\Entity\Embeddable\Money;
use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Subscription\SubscriptionManager;
use App\Subscription\SubscriptionMapper;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscriptionManagerTest extends TestCase
{
    private SubscriptionRepository|MockObject $subscriptionRepositoryMock;
    private SubscriptionMapper|MockObject $subscriptionMapperMock;
    private SubscriptionManager $subscriptionManager;

    protected function setUp(): void
    {
        $this->subscriptionRepositoryMock = $this->createMock(SubscriptionRepository::class);
        $this->subscriptionMapperMock = $this->createMock(SubscriptionMapper::class);

        $this->subscriptionManager = new SubscriptionManager(
            $this->subscriptionRepositoryMock,
            $this->subscriptionMapperMock
        );
    }

    public function testCreateNewSubscription(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $plan = new Plan();
        $plan->setName('Premium');
        $plan->setPrice(new Money(9.99, 'EUR'));

        $stripeSubscriptionId = 'sub_123456789';
        $subscription = new Subscription();

        $this->subscriptionMapperMock
            ->expects($this->once())
            ->method('mapEntity')
            ->with(
                $this->identicalTo($user),
                $this->identicalTo($plan),
                $this->equalTo($stripeSubscriptionId),
                $this->isInstanceOf(DateTimeImmutable::class),
                $this->isInstanceOf(DateTimeImmutable::class),
                $this->isNull()
            )
            ->willReturn($subscription)
        ;

        $this->subscriptionRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($subscription, true)
        ;

        $result = $this->subscriptionManager->create($user, $plan, $stripeSubscriptionId);

        $this->assertSame($subscription, $result);
        $this->assertNotNull($subscription->getCreatedAt());
        $this->assertEquals('test@example.com', $subscription->getCreatedBy());
    }

    public function testUpdateExistingSubscription(): void
    {
        $existingSubscription = new Subscription();

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setSubscription($existingSubscription);

        $plan = new Plan();
        $plan->setName('Premium');
        $plan->setPrice(new Money(9.99, 'EUR'));

        $stripeSubscriptionId = 'sub_123456789';
        $updatedSubscription = new Subscription();

        $this->subscriptionMapperMock
            ->expects($this->once())
            ->method('mapEntity')
            ->with(
                $this->identicalTo($user),
                $this->identicalTo($plan),
                $this->equalTo($stripeSubscriptionId),
                $this->isInstanceOf(DateTimeImmutable::class),
                $this->isInstanceOf(DateTimeImmutable::class),
                $this->identicalTo($existingSubscription)
            )
            ->willReturn($updatedSubscription)
        ;

        $this->subscriptionRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($updatedSubscription, true)
        ;

        $result = $this->subscriptionManager->create($user, $plan, $stripeSubscriptionId);

        $this->assertSame($updatedSubscription, $result);
        $this->assertNotNull($updatedSubscription->getUpdatedAt());
        $this->assertEquals('test@example.com', $updatedSubscription->getUpdatedBy());
    }
}
