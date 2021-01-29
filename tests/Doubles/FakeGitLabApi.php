<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Integration\GitLabApi;
use Buddy\Repman\Service\Integration\GitLabApi\Projects;

final class FakeGitLabApi implements GitLabApi
{
    private ?\Throwable $exception = null;

    public function setExceptionOnNextCall(?\Throwable $exception): void
    {
        $this->exception = $exception;
    }

    public function projects(string $accessToken): Projects
    {
        $this->throwExceptionIfSet();

        return new Projects([
            new GitLabApi\Project(123456, 'buddy-works/repman', 'https://gitlab.com/buddy-works/repman'),
        ]);
    }

    public function addHook(string $accessToken, int $projectId, string $hookUrl): void
    {
        $this->throwExceptionIfSet();
    }

    public function removeHook(string $accessToken, int $projectId, string $hookUrl): void
    {
        $this->throwExceptionIfSet();
    }

    private function throwExceptionIfSet(): void
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }
    }
}
