<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\PackageNormalizer;
use Composer\Package\Package;
use PHPUnit\Framework\TestCase;

final class PackageNormalizerTest extends TestCase
{
    public function testAddUidToPackageData(): void
    {
        $package = new Package('buddy-works/repman', '1.2.0.0', '1.2.0');
        $normalizer = new PackageNormalizer();

        $data = $normalizer->normalize($package);

        $this->assertArrayHasKey('uid', $data);
    }
}
