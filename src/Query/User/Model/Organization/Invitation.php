<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Organization;

final class Invitation
{
    public function __construct(private readonly string $email, private readonly string $role, private readonly string $token)
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

    public function token(): string
    {
        return $this->token;
    }
}
