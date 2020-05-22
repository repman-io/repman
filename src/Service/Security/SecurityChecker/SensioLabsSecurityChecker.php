<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security\SecurityChecker;

use Buddy\Repman\Service\Security\SecurityChecker;

final class SensioLabsSecurityChecker implements SecurityChecker
{
    /**
     * @return mixed[]
     */
    public function check(string $lockFile): array
    {
        // TODO: implement
        return [];
    }

    public function update(): void
    {
        // TODO: implement
    }
}
