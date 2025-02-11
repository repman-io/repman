<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ResetPassword
{
    public function __construct(private readonly string $token, private readonly string $password)
    {
    }

    public function token(): string
    {
        return $this->token;
    }

    public function password(): string
    {
        return $this->password;
    }
}
