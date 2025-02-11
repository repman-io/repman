<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Organization;

final class Member
{
    public function __construct(private readonly string $userId, private readonly string $email, private readonly string $role)
    {
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
