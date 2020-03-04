<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class AddHook
{
    private string $packageId;
    private string $repoName;
    private string $url;

    public function __construct(string $packageId, string $repoName, string $url)
    {
        $this->packageId = $packageId;
        $this->repoName = $repoName;
        $this->url = $url;
    }

    public function packageId(): string
    {
        return $this->packageId;
    }

    public function repoName(): string
    {
        return $this->repoName;
    }

    public function url(): string
    {
        return $this->url;
    }
}
