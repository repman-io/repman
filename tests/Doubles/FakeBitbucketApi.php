<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\BitbucketApi;

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

    public function repositories(string $accessToken): array
    {
        return [];
    }

    public function addHook(string $accessToken, string $fullName, string $hookUrl): void
    {
        // TODO: Implement addHook() method.
    }
}
