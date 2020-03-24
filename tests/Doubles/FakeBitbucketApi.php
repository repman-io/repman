<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\BitbucketApi;
use Buddy\Repman\Service\BitbucketApi\Repositories;

final class FakeBitbucketApi implements BitbucketApi
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

    public function repositories(string $accessToken): Repositories
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }

        return new Repositories([
            new BitbucketApi\Repository('{0f6dc6fe-f8ab-4a53-bb63-03042b80056f}', 'buddy-works/repman', 'https://bitbucket.org/buddy-works/repman.git'),
        ]);
    }

    public function addHook(string $accessToken, string $fullName, string $hookUrl): void
    {
        // TODO: Implement addHook() method.
    }

    public function removeHook(string $accessToken, string $fullName, string $hookUrl): void
    {
        // TODO: Implement removeHook() method.
    }
}
