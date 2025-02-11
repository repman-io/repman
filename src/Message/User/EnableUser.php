<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class EnableUser
{
    public function __construct(private readonly string $id)
    {
    }

    public function id(): string
    {
        return $this->id;
    }
}
