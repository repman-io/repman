<?php

declare(strict_types=1);

namespace Buddy\Repman\Security\Model\User;

final class Organization
{
    private string $alias;
    private string $name;
    private string $role;
    private bool $hasAnonymousAccess;

    public function __construct(string $alias, string $name, string $role, bool $hasAnonymousAccess)
    {
        $this->alias = $alias;
        $this->name = $name;
        $this->role = $role;
        $this->hasAnonymousAccess = $hasAnonymousAccess;
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
