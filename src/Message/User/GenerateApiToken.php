<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class GenerateApiToken
{
    public function __construct(private readonly string $userId, private readonly string $name)
    {
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function name(): string
    {
        return $this->name;
    }
}
