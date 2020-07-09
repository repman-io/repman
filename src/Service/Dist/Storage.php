<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Dist;

use Buddy\Repman\Service\Dist;
use Munus\Collection\GenericList;

interface Storage
{
    public function has(Dist $dist): bool;

    /**
     * @param string[] $headers
     */
    public function download(string $url, Dist $dist, array $headers = []): void;

    public function filename(Dist $dist): string;

    public function size(Dist $dist): int;

    /**
     * @return GenericList<string>
     */
    public function packages(string $repo): GenericList;

    public function remove(string $packageName): void;
}
