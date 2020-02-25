<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model\Installs;

final class Day
{
    private string $date;
    private int $installs;

    public function __construct(string $date, int $installs)
    {
        $this->date = $date;
        $this->installs = $installs;
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
