<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Organization;

final class Token implements \JsonSerializable
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

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'value' => $this->value(),
            'createdAt' => $this->createdAt()->format(\DateTime::ATOM),
            'lastUsedAt' => $this->lastUsedAt() !== null ? $this->lastUsedAt()->format(\DateTime::ATOM) : null,
        ];
    }
}
