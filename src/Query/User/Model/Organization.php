<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class Organization
{
    private string $id;
    private string $name;
    private string $alias;
    private string $ownerId;
    private ?string $token;

    public function __construct(string $id, string $name, string $alias, string $ownerId, ?string $token = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
        $this->ownerId = $ownerId;
        $this->token = $token;
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

    public function token(): ?string
    {
        return $this->token;
    }

    public function hasToken(): bool
    {
        return $this->token !== null;
    }

    public function isOwnedBy(string $userId): bool
    {
        return $this->ownerId === $userId;
    }
}
