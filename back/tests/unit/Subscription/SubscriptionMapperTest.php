<?php

namespace App\Tests\Unit\Subscription;

use App\Entity\Embeddable\Money;
use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Plan\PlanMapper;
use App\Plan\PlanModel;
use App\Subscription\SubscriptionMapper;
use App\Subscription\SubscriptionModel;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscriptionMapperTest extends TestCase
{
    private PlanMapper|MockObject $planMapper;
    private SubscriptionMapper $subscriptionMapper;

    protected function setUp(): void
    {
        $this->planMapper = $this->createMock(PlanMapper::class);
        $this->subscriptionMapper = new SubscriptionMapper(
            $this->planMapper,
        );
    }

    public function testMapEntityCreateNewSubscription(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $plan = new Plan();
        $plan->setName('Premium');
        $plan->setPrice(new Money(9.99, 'EUR'));

        $stripeSubscriptionId = 'sub_123456789';
        $startDate = new DateTimeImmutable('2023-01-01');
        $endDate = new DateTimeImmutable('2023-02-01');

        $subscription = $this->subscriptionMapper->mapEntity(
            $user,
            $plan,
            $stripeSubscriptionId,
            $startDate,
            $endDate,
            null
        );

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertSame($user, $subscription->getUser());
        $this->assertSame($plan, $subscription->getPlan());
        $this->assertEquals($stripeSubscriptionId, $subscription->getStripeSubscriptionId());
        $this->assertEquals($startDate, $subscription->getStartDate());
        $this->assertEquals($endDate, $subscription->getEndDate());
        $this->assertEquals($plan->getPrice(), $subscription->getPlan()->getPrice());
    }

    public function testMapEntityUpdateExistingSubscription(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $oldPlan = new Plan();
        $oldPlan->setName('Basic');
        $oldPlan->setPrice(new Money(4.99, 'EUR'));

        $newPlan = new Plan();
        $newPlan->setName('Premium');
        $newPlan->setPrice(new Money(9.99, 'EUR'));

        $existingSubscription = new Subscription();
        $existingSubscription->setUser($user);
        $existingSubscription->setPlan($oldPlan);
        $existingSubscription->setStripeSubscriptionId('old_sub_id');
        $existingSubscription->setStartDate(new DateTimeImmutable('2022-12-01'));
        $existingSubscription->setEndDate(new DateTimeImmutable('2023-01-01'));

        $newStripeSubscriptionId = 'new_sub_id';
        $newStartDate = new DateTimeImmutable('2023-01-01');
        $newEndDate = new DateTimeImmutable('2023-02-01');

        $updatedSubscription = $this->subscriptionMapper->mapEntity(
            $user,
            $newPlan,
            $newStripeSubscriptionId,
            $newStartDate,
            $newEndDate,
            $existingSubscription
        );

        $this->assertSame($existingSubscription, $updatedSubscription);
        $this->assertSame($user, $updatedSubscription->getUser());
        $this->assertSame($newPlan, $updatedSubscription->getPlan());
        $this->assertEquals($newStripeSubscriptionId, $updatedSubscription->getStripeSubscriptionId());
        $this->assertEquals($newStartDate, $updatedSubscription->getStartDate());
        $this->assertEquals($newEndDate, $updatedSubscription->getEndDate());
        $this->assertEquals($newPlan->getPrice(), $updatedSubscription->getPlan()->getPrice());
    }

    public function testMapModel(): void
    {
        $plan = new Plan()
            ->setName('Premium')
            ->setPrice(new Money(9.99, 'EUR'))
            ->setStripePriceId('price_123456789')
        ;

        $subscription = new Subscription()
            ->setStripeSubscriptionId('sub_123456789')
            ->setStartDate(new DateTimeImmutable('2023-01-01'))
            ->setEndDate(new DateTimeImmutable(' + 1 month'))
            ->setPlan($plan)
        ;

        $this->planMapper
            ->expects($this->once())
            ->method('mapModel')
            ->with($this->identicalTo($plan))
            ->willReturn(new PlanModel())
        ;

        $subscriptionModel = $this->subscriptionMapper->mapModel($subscription);

        $this->assertInstanceOf(SubscriptionModel::class, $subscriptionModel);
        $this->assertEquals('sub_123456789', $subscriptionModel->getStripeSubscriptionId());
        $this->assertEquals(new DateTimeImmutable('2023-01-01'), $subscriptionModel->getStartDate());
        $this->assertEquals(new DateTimeImmutable(' + 1 month')->format('Y-m-d'), $subscriptionModel->getEndDate()->format('Y-m-d'));
        $this->assertTrue($subscriptionModel->isActive());
    }
}
