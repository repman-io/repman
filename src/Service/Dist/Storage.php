<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Dist;

use Buddy\Repman\Service\Dist;
use Munus\Collection\GenericList;

interface Storage
{
    public function has(Dist $dist): bool;

    public function download(string $url, Dist $dist): void;

    public function filename(Dist $dist): string;

    /**
     * @return GenericList<string>
     */
    public function packages(string $repo): GenericList;
}
