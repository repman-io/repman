<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class GenerateToken
{
    private string $organizationId;
    private string $name;

    public function __construct(string $organizationId, string $name)
    {
        $this->organizationId = $organizationId;
        $this->name = $name;
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
