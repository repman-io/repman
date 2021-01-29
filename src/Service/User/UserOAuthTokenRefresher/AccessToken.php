<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User\UserOAuthTokenRefresher;

class AccessToken
{
    private string $token;

    private ?\DateTimeImmutable $expiresAt;

    public function __construct(string $token, ?\DateTimeImmutable $expiresAt = null)
    {
        $this->token = $token;
        $this->expiresAt = $expiresAt;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
