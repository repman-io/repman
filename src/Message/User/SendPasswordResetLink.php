<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class SendPasswordResetLink
{
    public function __construct(private readonly string $email, private readonly string $operatingSystem, private readonly string $browser)
    {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function operatingSystem(): string
    {
        return $this->operatingSystem;
    }

    public function browser(): string
    {
        return $this->browser;
    }
}
