<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User\UserOAuthTokenRefresher;

use DateTimeImmutable;

class AccessToken
{
    public function __construct(private readonly string $token, private readonly ?DateTimeImmutable $expiresAt = null)
    {
    }

    public function token(): string
    {
        return $this->token;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
