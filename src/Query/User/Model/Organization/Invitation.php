<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Organization;

final class Invitation
{
    private string $email;
    private string $role;
    private string $token;

    public function __construct(string $email, string $role, string $token)
    {
        $this->email = $email;
        $this->role = $role;
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

    public function token(): string
    {
        return $this->token;
    }
}
