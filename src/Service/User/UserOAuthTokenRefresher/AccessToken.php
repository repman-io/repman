<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User\UserOAuthTokenRefresher;

class AccessToken
{
    private string $token;

    private ?string $refreshToken;

    private ?\DateTimeImmutable $expiresAt;

    public function __construct(string $token, ?string $refreshToken = null, ?\DateTimeImmutable $expiresAt = null)
    {
        $this->token = $token;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = $expiresAt;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
