<?php

namespace App\Interface;

interface BlameableInterface
{
    /**
     * @psalm-suppress MissingReturnType
     *
     * @phpstan-ignore-next-line
     */
    public function setCreatedBy(string $user);

    /**
     * @psalm-suppress MissingReturnType
     *
     * @phpstan-ignore-next-line
     */
    public function setUpdatedBy(string $user);
}
