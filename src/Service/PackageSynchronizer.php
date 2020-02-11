<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Entity\Organization\Package;

interface PackageSynchronizer
{
    public function synchronize(Package $package): void;
}
