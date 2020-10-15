<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization\Package;

final class Update
{
    private string $packageId;
    private string $url;
    private int $keepLastReleases;

    public function __construct(string $packageId, string $url, int $keepLastReleases)
    {
        $this->packageId = $packageId;
        $this->url = $url;
        $this->keepLastReleases = $keepLastReleases;
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
}
