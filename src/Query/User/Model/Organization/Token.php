<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Organization;

final class Token
{
    private string $name;
    private string $value;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $lasUsedAt;

    public function __construct(string $name, string $value, \DateTimeImmutable $createdAt, ?\DateTimeImmutable $lasUsedAt)
    {
        $this->name = $name;
        $this->value = $value;
        $this->createdAt = $createdAt;
        $this->lasUsedAt = $lasUsedAt;
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

    public function lasUsedAt(): ?\DateTimeImmutable
    {
        return $this->lasUsedAt;
    }
}
