<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class ChangeAlias
{
    private string $organizationId;
    private string $alias;

    public function __construct(string $organizationId, string $alias)
    {
        $this->organizationId = $organizationId;
        $this->alias = $alias;
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
