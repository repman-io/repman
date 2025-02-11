<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ChangeRoles
{
    /**
     * @param string[] $roles
     */
    public function __construct(private readonly string $userId, private readonly array $roles)
    {
    }

    public function userId(): string
    {
        return $this->userId;
    }

    /**
     * @return string[]
     */
    public function roles(): array
    {
        return $this->roles;
    }
}
