<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Cache;

use Buddy\Repman\Service\Cache;
use Munus\Control\Option;

final class InMemoryCache implements Cache
{
    /**
     * @var array<string,string>
     */
    private array $cache;

    public function get(string $path, callable $supplier, int $expireTime = 0): Option
    {
        if (!isset($this->cache[$path])) {
            $this->cache[$path] = $supplier();
        }

        return Option::some($this->cache[$path]);
    }

    public function removeOld(string $path): void
    {
        // TODO: Implement remove() method.
    }
}
