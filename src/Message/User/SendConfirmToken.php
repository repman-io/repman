<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class SendConfirmToken
{
    private string $email;
    private string $token;

    public function __construct(string $email, string $token)
    {
        $this->email = $email;
        $this->token = $token;
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
