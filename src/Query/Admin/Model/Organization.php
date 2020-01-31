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
}
