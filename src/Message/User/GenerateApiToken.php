<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class GenerateApiToken
{
    private string $userId;
    private string $name;

    public function __construct(string $userId, string $name)
    {
        $this->userId = $userId;
        $this->name = $name;
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
