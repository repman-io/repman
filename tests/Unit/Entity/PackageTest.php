<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Tests\MotherObject\PackageMother;
use PHPUnit\Framework\TestCase;

final class PackageTest extends TestCase
{
    private Package $package;

    protected function setUp(): void
    {
        $this->package = PackageMother::withOrganization('vcs', 'https://url.to/package', 'buddy');
    }

    public function testOuathTokenNotFound(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->package->oauthToken();
    }

    public function testMetadataNotFound(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->package->metadata('not-exist');
    }
}
