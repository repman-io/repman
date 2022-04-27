<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\User;

final class AddOAuthToken
{
    private string $id;
    private string $userId;
    private string $type;
    private string $accessToken;
    private ?string $refreshToken = null;
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct(
        string $id,
        string $userId,
        string $type,
        string $accessToken,
        ?string $refreshToken = null,
        ?\DateTimeImmutable $expiresAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = $expiresAt;
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

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
