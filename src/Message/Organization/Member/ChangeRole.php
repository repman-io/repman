<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Member;

final class ChangeRole
{
    public function __construct(private readonly string $organizationId, private readonly string $userId, private readonly string $role)
    {
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
