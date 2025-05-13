<?php

declare(strict_types=1);

namespace Buddy\Repman\Security\Model\User;

final class Organization
{
    public function __construct(private readonly string $alias, private readonly string $name, private readonly string $role, private readonly bool $hasAnonymousAccess)
    {
    }

    public function alias(): string
    {
        return $this->alias;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function hasAnonymousAccess(): bool
    {
        return $this->hasAnonymousAccess;
    }
}
