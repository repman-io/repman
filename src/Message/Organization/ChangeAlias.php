<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class ChangeAlias
{
    public function __construct(private readonly string $organizationId, private readonly string $alias)
    {
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function alias(): string
    {
        return $this->alias;
    }
}
