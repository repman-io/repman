<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Member;

final class RemoveInvitation
{
    private string $organizationId;
    private string $token;

    public function __construct(string $organizationId, string $token)
    {
        $this->organizationId = $organizationId;
        $this->token = $token;
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
