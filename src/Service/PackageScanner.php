<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Entity\Organization\Package;

interface PackageScanner
{
    public function scan(Package $package): void;
}
