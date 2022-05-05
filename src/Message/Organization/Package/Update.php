<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Package;

final class Update
{
    private string $packageId;
    private string $url;
    private int $keepLastReleases;
    private bool $enableSecurityScan;

    public function __construct(string $packageId, string $url, int $keepLastReleases, bool $enableSecurityScan)
    {
        $this->packageId = $packageId;
        $this->url = $url;
        $this->keepLastReleases = $keepLastReleases;
        $this->enableSecurityScan = $enableSecurityScan;
    }

    public function packageId(): string
    {
        return $this->packageId;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function keepLastReleases(): int
    {
        return $this->keepLastReleases;
    }

    public function isEnabledSecurityScan(): bool
    {
        return $this->enableSecurityScan;
    }
}
