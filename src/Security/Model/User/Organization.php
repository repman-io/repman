<?php

declare(strict_types=1);

namespace Buddy\Repman\Security\Model\User;

final class Organization
{
    private string $alias;
    private string $name;
    private string $role;

    public function __construct(string $alias, string $name, string $role)
    {
        $this->alias = $alias;
        $this->name = $name;
        $this->role = $role;
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
}
