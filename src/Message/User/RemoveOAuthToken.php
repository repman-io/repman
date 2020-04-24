<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class RemoveOAuthToken
{
    private string $userId;
    private string $type;

    public function __construct(string $userId, string $type)
    {
        $this->userId = $userId;
        $this->type = $type;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function type(): string
    {
        return $this->type;
    }
}
