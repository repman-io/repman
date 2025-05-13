<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ChangeTimezone
{
    public function __construct(private readonly string $userId, private readonly string $timezone)
    {
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }
}
