<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Model;

final class Organization
{
    public function __construct(private readonly string $id, private readonly string $name, private readonly string $alias, private readonly int $packagesCount)
    {
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

    public function packagesCount(): int
    {
        return $this->packagesCount;
    }
}
