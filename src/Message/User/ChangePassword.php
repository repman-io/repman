<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ChangePassword
{
    private string $userId;
    private string $plainPassword;

    public function __construct(string $userId, string $plainPassword)
    {
        $this->userId = $userId;
        $this->plainPassword = $plainPassword;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function plainPassword(): string
    {
        return $this->plainPassword;
    }
}
