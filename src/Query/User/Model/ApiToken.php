<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use DateTimeImmutable;

final class ApiToken
{
    public function __construct(private readonly string $name, private readonly string $value, private readonly DateTimeImmutable $createdAt, private readonly ?DateTimeImmutable $lastUsedAt)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function lastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }
}
