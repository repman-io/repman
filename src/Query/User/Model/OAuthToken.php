<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class OAuthToken
{
    private string $type;
    private \DateTimeImmutable $createdAt;

    public function __construct(string $type, \DateTimeImmutable $createdAt)
    {
        $this->type = $type;
        $this->createdAt = $createdAt;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
