<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ChangeTimezone
{
    private string $userId;
    private string $timezone;

    public function __construct(string $userId, string $timezone)
    {
        $this->userId = $userId;
        $this->timezone = $timezone;
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
