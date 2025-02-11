<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ChangePassword
{
    public function __construct(private readonly string $userId, private readonly string $plainPassword)
    {
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
