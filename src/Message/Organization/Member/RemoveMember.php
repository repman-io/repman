<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Member;

final class RemoveMember
{
    private string $organizationId;
    private string $userId;

    public function __construct(string $organizationId, string $userId)
    {
        $this->organizationId = $organizationId;
        $this->userId = $userId;
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
