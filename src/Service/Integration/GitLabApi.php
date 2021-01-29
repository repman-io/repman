<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Integration;

use Buddy\Repman\Service\Integration\GitLabApi\Projects;

interface GitLabApi
{
    public function projects(string $accessToken): Projects;

    public function addHook(string $accessToken, int $projectId, string $hookUrl): void;

    public function removeHook(string $accessToken, int $projectId, string $hookUrl): void;
}
