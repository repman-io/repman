<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class CreateOrganization
{
    private string $id;
    private string $name;
    private string $ownerId;

    public function __construct(string $id, string $ownerId, string $name)
    {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->name = $name;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function ownerId(): string
    {
        return $this->ownerId;
    }

    public function name(): string
    {
        return $this->name;
    }
}
