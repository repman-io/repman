<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

use DateTime;
use DateTimeImmutable;
use JsonSerializable;

final class Token implements JsonSerializable
{
    public function __construct(private readonly string $name, private readonly string $value, private readonly DateTimeImmutable $createdAt, private readonly ?DateTimeImmutable $lastUsedAt)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'createdAt' => $this->createdAt->format(DateTime::ATOM),
            'lastUsedAt' => $this->lastUsedAt instanceof DateTimeImmutable ? $this->lastUsedAt->format(DateTime::ATOM) : null,
        ];
    }
}
