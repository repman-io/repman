<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Model;

final class Organization
{
    private string $id;
    private string $name;
    private string $alias;

    public function __construct(string $id, string $name, string $alias)
    {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
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
}
