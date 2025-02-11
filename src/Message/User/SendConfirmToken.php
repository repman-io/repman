<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class SendConfirmToken
{
    public function __construct(private readonly string $email, private readonly string $token)
    {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function token(): string
    {
        return $this->token;
    }
}
