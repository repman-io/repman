<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Member;

final class RemoveMember
{
    public function __construct(private readonly string $organizationId, private readonly string $userId)
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
}
