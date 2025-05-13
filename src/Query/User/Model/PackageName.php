<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class PackageName
{
    public function __construct(private readonly string $id, private readonly string $name, private readonly string $organization = '')
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

    public function organization(): string
    {
        return $this->organization;
    }
}
