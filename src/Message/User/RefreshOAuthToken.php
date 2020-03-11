<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class RefreshOAuthToken
{
    private string $userId;
    private string $tokenType;

    public function __construct(string $userId, string $tokenType)
    {
        $this->userId = $userId;
        $this->tokenType = $tokenType;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function tokenType(): string
    {
        return $this->tokenType;
    }
}
