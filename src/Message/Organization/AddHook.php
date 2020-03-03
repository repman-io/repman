<?php

declare(strict_types=1);

namespace Buddy\Repman\Message\Organization;

final class AddHook
{
    private string $packageId;
    private string $repoName;
    private string $oauthToken;
    private string $url;

    public function __construct(string $packageId, string $repoName, string $oauthToken, string $url)
    {
        $this->packageId = $packageId;
        $this->repoName = $repoName;
        $this->oauthToken = $oauthToken;
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

    public function oauthToken(): string
    {
        return $this->oauthToken;
    }

    public function url(): string
    {
        return $this->url;
    }
}
