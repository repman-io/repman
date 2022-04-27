<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Proxy\Model;

final class Package
{
    private int $downloads;
    private \DateTimeImmutable $lastDownload;

    public function __construct(int $downloads, \DateTimeImmutable $lastDownload)
    {
        $this->downloads = $downloads;
        $this->lastDownload = $lastDownload;
    }

    public function downloads(): int
    {
        return $this->downloads;
    }

    public function lastDownload(): \DateTimeImmutable
    {
        return $this->lastDownload;
    }
}
