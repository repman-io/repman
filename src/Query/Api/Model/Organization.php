<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

final class Organization implements \JsonSerializable
{
    private string $id;
    private string $name;
    private string $alias;
    private bool $hasAnonymousAccess;

    public function __construct(string $id, string $name, string $alias, bool $hasAnonymousAccess)
    {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
        $this->hasAnonymousAccess = $hasAnonymousAccess;
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
            'id' => $this->getId(),
            'name' => $this->getName(),
            'alias' => $this->getAlias(),
            'hasAnonymousAccess' => $this->getHasAnonymousAccess(),
        ];
    }
}
