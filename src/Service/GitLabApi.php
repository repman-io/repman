<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\GitLabApi\Projects;

interface GitLabApi
{
    public function projects(string $accessToken): Projects;

    public function addHook(string $accessToken, int $projectId, string $hookUrl): void;
}
