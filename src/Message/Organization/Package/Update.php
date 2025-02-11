<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Package;

final class Update
{
    public function __construct(private readonly string $packageId, private readonly string $url, private readonly int $keepLastReleases, private readonly bool $enableSecurityScan)
    {
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
