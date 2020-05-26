<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Security\SecurityChecker;

final class FakeSecurityChecker implements SecurityChecker
{
    /**
     * @return mixed[]
     */
    public function check(string $lockFile): array
    {
        return [];
    }

    public function update(): bool
    {
        return true;
    }
}
