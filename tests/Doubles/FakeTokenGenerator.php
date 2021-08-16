<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Organization\TokenGenerator;

final class FakeTokenGenerator implements TokenGenerator
{
    private ?string $nextToken = null;

    public function generate(): string
    {
        if ($this->nextToken !== null) {
            $nextToken = $this->nextToken;
            $this->nextToken = null;

            return $nextToken;
        }

        return bin2hex(random_bytes(32));
    }

    public function setNextToken(string $token): void
    {
        $this->nextToken = $token;
    }
}
