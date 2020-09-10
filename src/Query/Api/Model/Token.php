<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'value' => $this->getValue(),
            'createdAt' => $this->getCreatedAt()->format(\DateTime::ATOM),
            'lastUsedAt' => $this->getLastUsedAt() === null ? null : $this->getLastUsedAt()->format(\DateTime::ATOM),
        ];
    }
}
