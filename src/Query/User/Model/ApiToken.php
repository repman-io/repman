<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class ApiToken
{
    private string $name;
    private string $value;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $lastUsedAt;

    public function __construct(string $name, string $value, \DateTimeImmutable $createdAt, ?\DateTimeImmutable $lastUsedAt)
    {
        $this->name = $name;
        $this->value = $value;
        $this->createdAt = $createdAt;
        $this->lastUsedAt = $lastUsedAt;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function lastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }
}
