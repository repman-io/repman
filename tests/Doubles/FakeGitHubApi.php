<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Integration\GitHubApi;

final class FakeGitHubApi implements GitHubApi
{
    private string $primaryEmail = '';
    private ?\Throwable $exception = null;
    /**
     * @var string[]
     */
    private array $removedWebhooks = [];

    public function primaryEmail(string $accessToken): string
    {
        $this->throwExceptionIfSet();

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
        $this->throwExceptionIfSet();

        return [
            'buddy/repman',
        ];
    }

    public function addHook(string $accessToken, string $repo, string $url): void
    {
        $this->throwExceptionIfSet();
    }

    public function removeHook(string $accessToken, string $repo, string $url): void
    {
        $this->throwExceptionIfSet();
        $this->removedWebhooks[] = $repo;
    }

    /**
     * @return string[]
     */
    public function removedWebhooks(): array
    {
        return $this->removedWebhooks;
    }

    private function throwExceptionIfSet(): void
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }
    }
}
