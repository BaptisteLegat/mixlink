<?php

namespace App\Trait;

use App\Interface\BlameableInterface;
use App\Interface\TimestampableInterface;
use DateTime;

trait TraceableTrait
{
    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function setTimestampable(TimestampableInterface $timestampable, bool $isUpdate = false): void
    {
        $date = new DateTime();
        if (!$isUpdate) {
            $timestampable->setCreatedAt($date);
        }

        $timestampable->setUpdatedAt($date);
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function setBlameable(BlameableInterface $blameable, string $userEmail, bool $isUpdate = false): void
    {
        if (!$isUpdate) {
            $blameable->setCreatedBy($userEmail);
        }

        $blameable->setUpdatedBy($userEmail);
    }
}
