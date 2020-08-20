<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Proxy\Model;

final class Package
{
    private string $name;
    private int $downloads;
    private \DateTimeImmutable $lastDownload;

    public function __construct(string $name, int $downloads, \DateTimeImmutable $lastDownload)
    {
        $this->name = $name;
        $this->downloads = $downloads;
        $this->lastDownload = $lastDownload;
    }

    public function name(): string
    {
        return $this->name;
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
