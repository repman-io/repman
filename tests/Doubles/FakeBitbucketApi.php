<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Integration\BitbucketApi;
use Buddy\Repman\Service\Integration\BitbucketApi\Repositories;

final class FakeBitbucketApi implements BitbucketApi
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

    public function repositories(string $accessToken): Repositories
    {
        $this->throwExceptionIfSet();

        return new Repositories([
            new BitbucketApi\Repository('{0f6dc6fe-f8ab-4a53-bb63-03042b80056f}', 'buddy-works/repman', 'https://bitbucket.org/buddy-works/repman.git'),
        ]);
    }

    public function addHook(string $accessToken, string $fullName, string $hookUrl): void
    {
        $this->throwExceptionIfSet();
    }

    public function removeHook(string $accessToken, string $fullName, string $hookUrl): void
    {
        $this->throwExceptionIfSet();
        $this->removedWebhooks[] = $fullName;
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
