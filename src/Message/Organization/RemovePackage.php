<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class RemovePackage
{
    public function __construct(private readonly string $id, private readonly string $organizationId)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }
}
