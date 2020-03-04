<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Service\GitLabApi\Project;

interface GitLabApi
{
    /**
     * @return Project[]
     */
    public function projects(string $accessToken): array;

    public function addHook(string $accessToken, int $projectId, string $hookUrl): void;
}
