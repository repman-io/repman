<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\GitHubApi;

final class FakeGitHubApi implements GitHubApi
{
    private string $primaryEmail = '';
    private ?\Throwable $exception = null;

    public function primaryEmail(string $accessToken): string
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->primaryEmail;
    }

    public function setPrimaryEmail(string $primaryEmail): void
    {
        $this->primaryEmail = $primaryEmail;
    }

    public function setExceptionOnNextCall(?\Throwable $exception): void
    {
        $this->exception = $exception;
    }

    /**
     * @return array<int,string>
     */
    public function repositories(string $accessToken): array
    {
        return [
            'buddy/repman',
        ];
    }

    public function addHook(string $accessToken, string $repo, string $url): void
    {
        // TODO: Implement addHook() method.
    }

    public function removeHook(string $accessToken, string $repo, string $url): void
    {
        // TODO: Implement removeHook() method.
    }
}
