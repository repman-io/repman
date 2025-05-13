<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use DateTimeImmutable;

final class Version
{
    public function __construct(private readonly string $version, private readonly string $reference, private readonly int $size, private readonly DateTimeImmutable $date)
    {
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

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }
}
