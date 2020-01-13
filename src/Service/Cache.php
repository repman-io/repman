<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Munus\Control\Option;

interface Cache
{
    /**
     * @return Option<string>
     */
    public function get(string $path, callable $supplier, int $expireTime = 0): Option;

    public function put(string $path, string $contents): void;

    public function exists(string $path): bool;

    public function delete(string $path): void;
}
