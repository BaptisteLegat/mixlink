<?php

namespace App\Tests\Unit\Plan;

use App\Entity\Embeddable\Money;
use App\Entity\Plan;
use App\Plan\PlanMapper;
use App\Plan\PlanModel;
use PHPUnit\Framework\TestCase;

class PlanMapperTest extends TestCase
{
    private PlanMapper $planMapper;

    protected function setUp(): void
    {
        $this->planMapper = new PlanMapper();
    }

    public function testMapModel(): void
    {
        $plan = new Plan();
        $plan->setName('Premium')
            ->setPrice(new Money(999, 'EUR'))
            ->setStripePriceId('price_123456789'
            );

        $planModel = $this->planMapper->mapModel($plan);

        $this->assertInstanceOf(PlanModel::class, $planModel);
        $this->assertEquals($plan->getId(), $planModel->getId());
        $this->assertEquals('Premium', $planModel->getName());
        $this->assertEquals(9.99, $planModel->getPrice());
        $this->assertEquals('EUR', $planModel->getCurrency());
        $this->assertEquals('price_123456789', $planModel->getStripePriceId());
    }

    public function testMapModelWithNullPrice(): void
    {
        $plan = new Plan();
        $plan->setName('Free')
            ->setPrice(new Money(null, 'EUR'))
            ->setStripePriceId('price_free')
        ;

        $planModel = $this->planMapper->mapModel($plan);

        $this->assertInstanceOf(PlanModel::class, $planModel);
        $this->assertEquals($plan->getId(), $planModel->getId());
        $this->assertEquals('Free', $planModel->getName());
        $this->assertEquals(0, $planModel->getPrice());
        $this->assertEquals('EUR', $planModel->getCurrency());
        $this->assertEquals('price_free', $planModel->getStripePriceId());
    }

    public function testMapModelWithZeroPrice(): void
    {
        $plan = new Plan();
        $plan->setName('Basic')
            ->setPrice(new Money(0, 'USD'))
            ->setStripePriceId('price_basic')
        ;

        $planModel = $this->planMapper->mapModel($plan);

        $this->assertInstanceOf(PlanModel::class, $planModel);
        $this->assertEquals($plan->getId(), $planModel->getId());
        $this->assertEquals('Basic', $planModel->getName());
        $this->assertEquals(0, $planModel->getPrice());
        $this->assertEquals('USD', $planModel->getCurrency());
        $this->assertEquals('price_basic', $planModel->getStripePriceId());
    }
}
