<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class ChangeAnonymousAccess
{
    public function __construct(private readonly string $organizationId, private readonly bool $hasAnonymousAccess)
    {
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
