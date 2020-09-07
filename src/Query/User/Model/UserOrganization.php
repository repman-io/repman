<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class UserOrganization implements \JsonSerializable
{
    private string $name;
    private string $alias;
    private bool $hasAnonymousAccess;

    public function __construct(string $name, string $alias, bool $hasAnonymousAccess)
    {
        $this->name = $name;
        $this->alias = $alias;
        $this->hasAnonymousAccess = $hasAnonymousAccess;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    public function hasAnonymousAccess(): bool
    {
        return $this->hasAnonymousAccess;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'alias' => $this->alias(),
            'hasAnonymousAccess' => $this->hasAnonymousAccess(),
        ];
    }
}
