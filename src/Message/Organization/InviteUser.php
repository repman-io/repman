<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class InviteUser
{
    private string $email;
    private string $role;
    private string $organizationId;
    private string $token;

    public function __construct(string $email, string $role, string $organizationId, string $token)
    {
        $this->email = $email;
        $this->role = $role;
        $this->organizationId = $organizationId;
        $this->token = $token;
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
