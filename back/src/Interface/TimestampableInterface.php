<?php

namespace App\Interface;

use DateTime;

interface TimestampableInterface
{
    /**
     * @phpstan-ignore-next-line
     *
     * @psalm-suppress MissingReturnType
     */
    public function setCreatedAt(DateTime $createdAt);

    /**
     * @phpstan-ignore-next-line
     *
     * @psalm-suppress MissingReturnType
     */
    public function setUpdatedAt(DateTime $updatedAt);
}
