<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class PackageName
{
    private string $id;
    private string $name;
    private string $organization;

    public function __construct(string $id, string $name, string $organization = '')
    {
        $this->id = $id;
        $this->name = $name;
        $this->organization = $organization;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function organization(): string
    {
        return $this->organization;
    }
}
