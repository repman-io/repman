<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class CreateOrganization
{
    public function __construct(private readonly string $id, private readonly string $ownerId, private readonly string $name)
    {
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
