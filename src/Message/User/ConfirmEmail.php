<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class ConfirmEmail
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function token(): string
    {
        return $this->token;
    }
}
