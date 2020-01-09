<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Munus\Control\Option;

interface Cache
{
    /**
     * @return Option<string>
     */
    public function get(string $path, callable $supplier): Option;

    public function delete(string $path): void;
}
