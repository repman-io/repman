<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Model;

final class Organization
{
    private string $id;
    private string $name;
    private string $alias;
    private string $ownerEmail;
    private int $packagesCount;

    public function __construct(string $id, string $name, string $alias, string $ownerEmail, int $packagesCount)
    {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
        $this->ownerEmail = $ownerEmail;
        $this->packagesCount = $packagesCount;
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

    public function packagesCount(): int
    {
        return $this->packagesCount;
    }
}
