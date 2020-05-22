<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

final class FakeProcess
{
    public function run(): int
    {
        return 0;
    }

    public function isSuccessful(): bool
    {
        return true;
    }
}
