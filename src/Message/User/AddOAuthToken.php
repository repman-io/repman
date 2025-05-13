<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

use DateTimeImmutable;

final class AddOAuthToken
{
    public function __construct(private readonly string $id, private readonly string $userId, private readonly string $type, private readonly string $accessToken, private readonly ?string $refreshToken = null, private readonly ?DateTimeImmutable $expiresAt = null)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function refreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
