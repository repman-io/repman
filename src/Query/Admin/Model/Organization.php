<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Model;

final class Organization
{
    private string $id;
    private string $name;
    private string $alias;
    private int $packagesCount;

    public function __construct(string $id, string $name, string $alias, int $packagesCount)
    {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
        $this->packagesCount = $packagesCount;
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
