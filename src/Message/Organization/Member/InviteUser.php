<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Member;

final class InviteUser
{
    public function __construct(private readonly string $email, private readonly string $role, private readonly string $organizationId, private readonly string $token)
    {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function role(): string
    {
        return $this->role;
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
