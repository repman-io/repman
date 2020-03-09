<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\GitLabApi;
use Buddy\Repman\Service\GitLabApi\Projects;

final class FakeGitLabApi implements GitLabApi
{
    public function projects(string $accessToken): Projects
    {
        return new Projects([
            new GitLabApi\Project(123456, 'buddy-works/repman', 'https://gitlab.com/buddy-works/repman'),
        ]);
    }

    public function addHook(string $accessToken, int $projectId, string $hookUrl): void
    {
        // TODO: Implement addHook() method.
    }
}
