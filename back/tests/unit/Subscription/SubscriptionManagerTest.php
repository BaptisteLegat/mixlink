<?php

namespace App\Tests\Unit\Subscription;

use App\Entity\Embeddable\Money;
use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
use App\Subscription\SubscriptionMapper;
use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Stripe\StripeObject;
use Stripe\Subscription as StripeSubscription;

class SubscriptionManagerTest extends TestCase
{
    private SubscriptionRepository|MockObject $subscriptionRepositoryMock;
    private SubscriptionMapper|MockObject $subscriptionMapperMock;
    private StripeService|MockObject $stripeServiceMock;
    private LoggerInterface|MockObject $loggerMock;
    private SubscriptionManager $subscriptionManager;

    protected function setUp(): void
    {
        $this->subscriptionRepositoryMock = $this->createMock(SubscriptionRepository::class);
        $this->subscriptionMapperMock = $this->createMock(SubscriptionMapper::class);
        $this->stripeServiceMock = $this->createMock(StripeService::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->subscriptionManager = new SubscriptionManager(
            $this->subscriptionRepositoryMock,
            $this->subscriptionMapperMock,
            $this->stripeServiceMock,
            $this->loggerMock
        );
    }

    public function testCreateNewSubscription(): void
    {
        $user = new User()->setEmail('test@example.com');

        $plan = new Plan()
            ->setName('Premium')
            ->setPrice(new Money(999, 'EUR'))
        ;

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

        $user = new User()
            ->setEmail('test@example.com')
            ->setSubscription($existingSubscription)
        ;

        $plan = new Plan()
            ->setName('Premium')
            ->setPrice(new Money(999, 'EUR'))
        ;

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

    public function testCancelSubscriptionNoSubscription(): void
    {
        $user = new User();

        $result = $this->subscriptionManager->cancelSubscription($user);

        $this->assertFalse($result);
    }

    public function testCancelSubscriptionNoStripeId(): void
    {
        $subscription = new Subscription();
        $user = new User()->setSubscription($subscription);

        $result = $this->subscriptionManager->cancelSubscription($user);

        $this->assertFalse($result);
    }

    public function testCancelSubscriptionWithCanceledStatus(): void
    {
        $subscription = new Subscription()->setStripeSubscriptionId('sub_123456');
        $user = new User()->setSubscription($subscription);

        $stripeSubscription = new StripeSubscription();
        $stripeSubscription->status = 'canceled';

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with('sub_123456')
            ->willReturn($stripeSubscription)
        ;

        $this->subscriptionRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(function (Subscription $sub) {
                    return 'canceled' === $sub->getStatus()
                           && $sub->getCanceledAt() instanceof DateTimeImmutable
                           && $sub->getEndDate() instanceof DateTimeImmutable;
                }),
                true
            )
        ;

        $result = $this->subscriptionManager->cancelSubscription($user);

        $this->assertTrue($result);
    }

    public function testCancelSubscriptionWithCancelAtPeriodEnd(): void
    {
        $subscription = new Subscription()->setStripeSubscriptionId('sub_123456');
        $user = new User()->setSubscription($subscription);

        $stripeSubscription = new StripeSubscription();
        $stripeSubscription->status = 'active';
        $stripeSubscription->cancel_at_period_end = true;
        $stripeSubscription->current_period_end = time() + 86400; // tomorrow

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with('sub_123456')
            ->willReturn($stripeSubscription)
        ;

        $this->subscriptionRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(function (Subscription $sub) {
                    return 'canceled' === $sub->getStatus()
                           && $sub->getCanceledAt() instanceof DateTimeImmutable
                           && $sub->getEndDate() instanceof DateTimeImmutable;
                }),
                true
            )
        ;

        $result = $this->subscriptionManager->cancelSubscription($user);

        $this->assertTrue($result);
    }

    public function testCancelSubscriptionWithException(): void
    {
        $subscription = new Subscription()->setStripeSubscriptionId('sub_123456');
        $user = new User()->setSubscription($subscription);

        $exception = new Exception('Stripe API error');

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with('sub_123456')
            ->willThrowException($exception)
        ;

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Error canceling subscription'),
                $this->arrayHasKey('trace')
            )
        ;

        $this->subscriptionRepositoryMock
            ->expects($this->never())
            ->method('save')
        ;

        $result = $this->subscriptionManager->cancelSubscription($user);

        $this->assertFalse($result);
    }

    public function testCancelSubscriptionWithInvalidResponse(): void
    {
        $subscription = new Subscription()->setStripeSubscriptionId('sub_123456');
        $user = new User()->setSubscription($subscription);

        $invalidResponse = new StripeObject();

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with('sub_123456')
            ->willReturn($invalidResponse)
        ;

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Error canceling subscription'),
                $this->arrayHasKey('trace')
            )
        ;

        $this->subscriptionRepositoryMock
            ->expects($this->never())
            ->method('save')
        ;

        $result = $this->subscriptionManager->cancelSubscription($user);

        $this->assertFalse($result);
    }

    public function testChangeSubscriptionPlanNoSubscription(): void
    {
        $user = new User();
        $plan = new Plan();

        $result = $this->subscriptionManager->changeSubscriptionPlan($user, $plan);

        $this->assertFalse($result);
    }

    public function testChangeSubscriptionPlanNoStripeId(): void
    {
        $subscription = new Subscription();
        $user = new User()->setSubscription($subscription);
        $plan = new Plan();

        $result = $this->subscriptionManager->changeSubscriptionPlan($user, $plan);

        $this->assertFalse($result);
    }

    public function testChangeSubscriptionPlanNoPriceId(): void
    {
        $subscription = new Subscription()->setStripeSubscriptionId('sub_123456');
        $user = new User()->setSubscription($subscription);
        $plan = new Plan()->setName('Premium');

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('getPriceIdForPlan')
            ->with('Premium')
            ->willReturn(null)
        ;

        $result = $this->subscriptionManager->changeSubscriptionPlan($user, $plan);

        $this->assertFalse($result);
    }

    public function testChangeSubscriptionPlanSuccess(): void
    {
        $subscription = new Subscription()->setStripeSubscriptionId('sub_123456');
        $user = new User()->setSubscription($subscription);
        $plan = new Plan()->setName('Premium');

        $stripeSubscription = new StripeSubscription();
        $stripeSubscription->status = 'active';
        $stripeSubscription->current_period_end = time() + 86400; // tomorrow

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('getPriceIdForPlan')
            ->with('Premium')
            ->willReturn('price_123456')
        ;

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('changeSubscriptionPlan')
            ->with('sub_123456', 'price_123456')
            ->willReturn($stripeSubscription)
        ;

        $this->subscriptionRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(function (Subscription $sub) use ($plan) {
                    return $sub->getPlan() === $plan
                           && 'active' === $sub->getStatus()
                           && null === $sub->getCanceledAt()
                           && $sub->getEndDate() instanceof DateTimeImmutable;
                }),
                true
            )
        ;

        $result = $this->subscriptionManager->changeSubscriptionPlan($user, $plan);

        $this->assertTrue($result);
    }

    public function testChangeSubscriptionPlanWithException(): void
    {
        $subscription = new Subscription()->setStripeSubscriptionId('sub_123456');
        $user = new User()->setSubscription($subscription);
        $plan = new Plan()->setName('Premium');

        $exception = new Exception('Stripe API error');

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('getPriceIdForPlan')
            ->with('Premium')
            ->willReturn('price_123456')
        ;

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('changeSubscriptionPlan')
            ->with('sub_123456', 'price_123456')
            ->willThrowException($exception)
        ;

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Error changing subscription plan'),
                $this->arrayHasKey('trace')
            )
        ;

        $this->subscriptionRepositoryMock
            ->expects($this->never())
            ->method('save')
        ;

        $result = $this->subscriptionManager->changeSubscriptionPlan($user, $plan);

        $this->assertFalse($result);
    }

    public function testChangeSubscriptionPlanWithInvalidResponse(): void
    {
        $subscription = new Subscription()->setStripeSubscriptionId('sub_123456');
        $user = new User()->setSubscription($subscription);
        $plan = new Plan()->setName('Premium');

        $invalidResponse = new StripeObject();

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('getPriceIdForPlan')
            ->with('Premium')
            ->willReturn('price_123456')
        ;

        $this->stripeServiceMock
            ->expects($this->once())
            ->method('changeSubscriptionPlan')
            ->with('sub_123456', 'price_123456')
            ->willReturn($invalidResponse)
        ;

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Error changing subscription plan'),
                $this->arrayHasKey('trace')
            )
        ;

        $this->subscriptionRepositoryMock
            ->expects($this->never())
            ->method('save')
        ;

        $result = $this->subscriptionManager->changeSubscriptionPlan($user, $plan);

        $this->assertFalse($result);
    }
}
