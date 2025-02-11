<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Installs;

final class Day
{
    public function __construct(private readonly string $date, private readonly int $installs)
    {
    }

    public function date(): string
    {
        return $this->date;
    }

    public function installs(): int
    {
        return $this->installs;
    }
}
