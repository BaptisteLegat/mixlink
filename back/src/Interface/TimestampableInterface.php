<?php

namespace App\Interface;

use DateTime;

interface TimestampableInterface
{
    /**
     * @psalm-suppress MissingReturnType
     *
     * @phpstan-ignore-next-line
     */
    public function setCreatedAt(DateTime $createdAt);

    /**
     * @psalm-suppress MissingReturnType
     *
     * @phpstan-ignore-next-line
     */
    public function setUpdatedAt(DateTime $updatedAt);
}
