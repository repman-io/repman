<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Organization\TokenGenerator;

final class FakeTokenGenerator implements TokenGenerator
{
    private string $nextToken;

    public function __construct()
    {
        $this->nextToken = (string) time();
    }

    public function generate(): string
    {
        return $this->nextToken;
    }

    public function setNextToken(string $token): void
    {
        $this->nextToken = $token;
    }
}
