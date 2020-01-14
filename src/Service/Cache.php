<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Munus\Control\Option;

interface Cache
{
    /**
     * @return Option<array<mixed>>
     */
    public function get(string $path, callable $supplier, int $expireTime = 0): Option;

    public function removeOld(string $path): void;
}
