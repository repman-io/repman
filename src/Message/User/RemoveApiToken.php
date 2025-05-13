<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class RemoveApiToken
{
    public function __construct(private readonly string $userId, private readonly string $token)
    {
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function token(): string
    {
        return $this->token;
    }
}
