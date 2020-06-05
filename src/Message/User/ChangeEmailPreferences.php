<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ChangeEmailPreferences
{
    private string $userId;
    private bool $emailScanResult;

    public function __construct(string $userId, bool $emailScanResult)
    {
        $this->userId = $userId;
        $this->emailScanResult = $emailScanResult;
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
