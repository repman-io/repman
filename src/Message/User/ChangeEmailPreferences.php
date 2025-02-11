<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ChangeEmailPreferences
{
    public function __construct(private readonly string $userId, private readonly bool $emailScanResult)
    {
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function emailScanResult(): bool
    {
        return $this->emailScanResult;
    }
}
