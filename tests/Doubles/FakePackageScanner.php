<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Service\Security\PackageScanner;

final class FakePackageScanner implements PackageScanner
{
    /**
     * @var string[]
     */
    private array $scannedPackages;

    public function scan(Package $package): void
    {
        $this->scannedPackages[] = $package->id()->toString();
    }

    public function wasScanned(string $id): bool
    {
        return in_array($id, $this->scannedPackages, true);
    }
}
