<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ChangePassword
{
    private string $userId;
    private string $password;

    public function __construct(string $userId, string $password)
    {
        $this->userId = $userId;
        $this->password = $password;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function password(): string
    {
        return $this->password;
    }
}
