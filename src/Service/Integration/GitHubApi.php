<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration;

interface GitHubApi
{
    public function primaryEmail(string $accessToken): string;

    /**
     * @return array<int,string>
     */
    public function repositories(string $accessToken): array;

    public function addHook(string $accessToken, string $repo, string $url): void;

    public function removeHook(string $accessToken, string $repo, string $url): void;
}
