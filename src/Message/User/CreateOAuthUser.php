<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class CreateOAuthUser
{
    public function __construct(private readonly string $email)
    {
    }

    public function email(): string
    {
        return $this->email;
    }
}
