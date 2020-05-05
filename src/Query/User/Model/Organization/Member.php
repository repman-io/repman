<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Organization;

final class Member
{
    private string $userId;
    private string $email;
    private string $role;

    public function __construct(string $userId, string $email, string $role)
    {
        $this->userId = $userId;
        $this->email = $email;
        $this->role = $role;
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
