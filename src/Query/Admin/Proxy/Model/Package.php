<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Proxy\Model;

use DateTimeImmutable;

final class Package
{
    public function __construct(private readonly int $downloads, private readonly DateTimeImmutable $lastDownload)
    {
    }

    public function downloads(): int
    {
        return $this->downloads;
    }

    public function lastDownload(): DateTimeImmutable
    {
        return $this->lastDownload;
    }
}
