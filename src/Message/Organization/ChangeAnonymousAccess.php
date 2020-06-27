<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class ChangeAnonymousAccess
{
    private string $organizationId;
    private bool $hasAnonymousAccess;

    public function __construct(string $organizationId, bool $hasAnonymousAccess)
    {
        $this->organizationId = $organizationId;
        $this->hasAnonymousAccess = $hasAnonymousAccess;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function hasAnonymousAccess(): bool
    {
        return $this->hasAnonymousAccess;
    }
}
