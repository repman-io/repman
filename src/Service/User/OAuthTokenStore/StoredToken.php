<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User\OAuthTokenStore;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\AccessToken;

/**
 * An OAuth token as currently persisted, read under a row lock. It may be newer
 * than the in-memory entity when a concurrent worker has already refreshed it.
 */
final class StoredToken
{
    private string $accessToken;

    private ?string $refreshToken;

    private ?\DateTimeImmutable $expiresAt;

    public function __construct(string $accessToken, ?string $refreshToken = null, ?\DateTimeImmutable $expiresAt = null)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = $expiresAt;
    }

    public function refreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return (new \DateTimeImmutable()) > $this->expiresAt->modify(OAuthToken::EXPIRATION_MARGIN);
    }

    public function asAccessToken(): AccessToken
    {
        return new AccessToken($this->accessToken, $this->expiresAt, $this->refreshToken);
    }
}
