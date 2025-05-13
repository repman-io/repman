<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class RegenerateToken
{
    public function __construct(private readonly string $organizationId, private readonly string $token)
    {
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function token(): string
    {
        return $this->token;
    }
}
