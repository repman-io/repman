<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class SendPasswordResetLink
{
    private string $email;
    private string $operatingSystem;
    private string $browser;

    public function __construct(string $email, string $operatingSystem, string $browser)
    {
        $this->email = $email;
        $this->operatingSystem = $operatingSystem;
        $this->browser = $browser;
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
