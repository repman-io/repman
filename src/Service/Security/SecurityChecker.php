<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Security;

interface SecurityChecker
{
    /**
     * @return mixed[]
     */
    public function check(string $lockFile): array;

    public function update(): bool;
}
