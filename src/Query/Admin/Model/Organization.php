<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Model;

final class Organization
{
    private string $id;
    private string $name;
    private string $alias;
    private string $ownerEmail;

    public function __construct(string $id, string $name, string $alias, string $ownerEmail)
    {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
        $this->ownerEmail = $ownerEmail;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    public function ownerEmail(): string
    {
        return $this->ownerEmail;
    }
}
