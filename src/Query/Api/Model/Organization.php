<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

use JsonSerializable;

final class Organization implements JsonSerializable
{
    public function __construct(private readonly string $id, private readonly string $name, private readonly string $alias, private readonly bool $hasAnonymousAccess)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getHasAnonymousAccess(): bool
    {
        return $this->hasAnonymousAccess;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'alias' => $this->alias,
            'hasAnonymousAccess' => $this->hasAnonymousAccess,
        ];
    }
}
