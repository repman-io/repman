<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Service\Security\PackageScanner;

final class FakePackageScanner implements PackageScanner
{
    public function scan(Package $package): void
    {
    }
}
