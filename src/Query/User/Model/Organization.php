<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class Organization
{
    private string $id;
    private string $name;
    private string $alias;
    private string $ownerId;

    public function __construct(string $id, string $name, string $alias, string $ownerId)
    {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
        $this->ownerId = $ownerId;
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

    public function isOwnedBy(string $userId): bool
    {
        return $this->ownerId === $userId;
    }
}
