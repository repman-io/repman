<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class ChangeName
{
    public function __construct(private readonly string $organizationId, private readonly string $name)
    {
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }
}
