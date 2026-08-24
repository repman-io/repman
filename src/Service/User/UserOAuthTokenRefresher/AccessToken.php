<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User\UserOAuthTokenRefresher;

class AccessToken
{
    private string $token;

    private ?\DateTimeImmutable $expiresAt;

    private ?string $refreshToken;

    public function __construct(string $token, ?\DateTimeImmutable $expiresAt = null, ?string $refreshToken = null)
    {
        $this->token = $token;
        $this->expiresAt = $expiresAt;
        $this->refreshToken = $refreshToken;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Providers with rotating refresh tokens (e.g. Bitbucket) return a new
     * refresh token with every refresh and invalidate the previous one.
     */
    public function refreshToken(): ?string
    {
        return $this->refreshToken;
    }
}
