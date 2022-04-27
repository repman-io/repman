<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class AddDownload
{
    private string $packageId;
    private string $version;
    private \DateTimeImmutable $date;
    private ?string $ip;
    private ?string $userAgent;

    public function __construct(string $packageId, string $version, \DateTimeImmutable $date, ?string $ip, ?string $userAgent)
    {
        $this->packageId = $packageId;
        $this->version = $version;
        $this->date = $date;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
    }

    public function packageId(): string
    {
        return $this->packageId;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function ip(): ?string
    {
        return $this->ip;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }
}
