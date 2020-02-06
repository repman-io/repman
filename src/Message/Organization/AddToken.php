<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class AddToken
{
    private string $organizationId;
    private string $value;
    private string $name;

    public function __construct(string $organizationId, string $value, string $name)
    {
        $this->organizationId = $organizationId;
        $this->value = $value;
        $this->name = $name;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function name(): string
    {
        return $this->name;
    }
}
