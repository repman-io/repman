<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class RegenerateApiToken
{
    private string $userId;
    private string $token;

    public function __construct(string $userId, string $token)
    {
        $this->userId = $userId;
        $this->token = $token;
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
