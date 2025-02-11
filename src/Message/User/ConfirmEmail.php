<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ConfirmEmail
{
    public function __construct(private readonly string $token)
    {
    }

    public function token(): string
    {
        return $this->token;
    }
}
