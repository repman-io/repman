<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Member;

final class ChangeRole
{
    private string $organizationId;
    private string $userId;
    private string $role;

    public function __construct(string $organizationId, string $userId, string $role)
    {
        $this->organizationId = $organizationId;
        $this->userId = $userId;
        $this->role = $role;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function role(): string
    {
        return $this->role;
    }
}
