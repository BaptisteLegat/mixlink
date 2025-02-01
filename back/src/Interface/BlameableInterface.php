<?php

namespace App\Interface;

interface BlameableInterface
{
    /**
     * @phpstan-ignore-next-line
     *
     * @psalm-suppress MissingReturnType
     */
    public function setCreatedBy(string $user);

    /**
     * @phpstan-ignore-next-line
     *
     * @psalm-suppress MissingReturnType
     */
    public function setUpdatedBy(string $user);
}
