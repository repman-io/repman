<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class RemoveOAuthToken
{
    public function __construct(private readonly string $userId, private readonly string $type)
    {
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
