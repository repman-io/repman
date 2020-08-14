<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class Version
{
    private string $id;
    private string $version;
    private string $reference;
    private int $size;
    private \DateTimeImmutable $date;

    public function __construct(string $id, string $version, string $reference, int $size, \DateTimeImmutable $date)
    {
        $this->id = $id;
        $this->version = $version;
        $this->reference = $reference;
        $this->size = $size;
        $this->date = $date;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }
}
