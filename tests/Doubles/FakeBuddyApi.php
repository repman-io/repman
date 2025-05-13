<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Integration\BuddyApi;
use Throwable;

final class FakeBuddyApi implements BuddyApi
{
    private string $primaryEmail = '';

    private ?Throwable $exception = null;

    public function primaryEmail(string $accessToken): string
    {
        if ($this->exception instanceof Throwable) {
            throw $this->exception;
        }

        return $this->primaryEmail;
    }

    public function setPrimaryEmail(string $primaryEmail): void
    {
        $this->primaryEmail = $primaryEmail;
    }

    public function setExceptionOnNextCall(?Throwable $exception): void
    {
        $this->exception = $exception;
    }
}
