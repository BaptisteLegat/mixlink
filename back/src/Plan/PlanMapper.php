<?php

namespace App\Plan;

use App\Entity\Plan;

class PlanMapper
{
    public function mapModel(Plan $plan): PlanModel
    {
        $planModel = new PlanModel();

        $planModel
            ->setId((string) $plan->getId())
            ->setName($plan->getName())
            ->setPrice(($plan->getPrice()->getAmount() ?? 0) / 100)
            ->setCurrency($plan->getPrice()->getCurrency())
            ->setStripePriceId($plan->getStripePriceId())
        ;

        return $planModel;
    }
}
