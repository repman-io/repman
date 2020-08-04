<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Entity;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Tests\MotherObject\PackageMother;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class PackageTest extends TestCase
{
    private Package $package;

    protected function setUp(): void
    {
        $this->package = PackageMother::withOrganization('vcs', 'https://url.to/package', 'buddy');
    }

    public function testCheckNameOnSuccessSync(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->package->syncSuccess('../invalid/name', 'desc', '1.2.0.0', [], new \DateTimeImmutable());
    }

    public function testSyncSuccessRemovesUnencounteredVersions(): void
    {
        $this->package->addOrUpdateVersion($version1 = new Version(Uuid::uuid4(), '1.0.0', 'someref', 1234, new \DateTimeImmutable()));
        $this->package->addOrUpdateVersion($version2 = new Version(Uuid::uuid4(), '1.0.1', 'anotherref', 5678, new \DateTimeImmutable()));
        $this->package->addOrUpdateVersion($version3 = new Version(Uuid::uuid4(), '1.1.0', 'lastref', 6543, new \DateTimeImmutable()));

        $this->package->syncSuccess('some/package', 'desc', '1.1.0', ['1.0.0', '1.1.0'], new \DateTimeImmutable());

        self::assertCount(2, $this->package->versions());
        self::assertContains($version1, $this->package->versions());
        self::assertNotContains($version2, $this->package->versions());
        self::assertContains($version3, $this->package->versions());
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

    public function testPackageGetProperties(): void
    {
        $date = new \DateTimeImmutable();
        $version = new Version($id = Uuid::uuid4(), '1.0.0', 'someref', 1234, $date);
        $this->package->addOrUpdateVersion($version);

        self::assertInstanceOf(Version::class, $returnedVersion = $this->package->getVersion('1.0.0'));
        self::assertEquals($id, $returnedVersion->id());
        self::assertEquals('1.0.0', $returnedVersion->version());
        self::assertEquals('someref', $returnedVersion->reference());
        self::assertEquals(1234, $returnedVersion->size());
        self::assertEquals($date, $returnedVersion->date());
    }

    public function testPackageNonExisting(): void
    {
        $version = new Version(Uuid::uuid4(), '1.0.0', 'someref', 1234, new \DateTimeImmutable());
        $this->package->addOrUpdateVersion($version);

        self::assertEquals(false, $this->package->getVersion('1.0.1'));
    }

    public function testPackageUpdateVersion(): void
    {
        $date = new \DateTimeImmutable('tomorrow');
        // Make sure the dates do not match so we can test that it is updated
        $version = new Version($id1 = Uuid::uuid4(), '1.0.0', 'someref', 1234, new \DateTimeImmutable('today'));
        $versionUpdated = new Version($id2 = Uuid::uuid4(), '1.0.0', 'newref', 5678, $date);
        $this->package->addOrUpdateVersion($version);
        $this->package->addOrUpdateVersion($versionUpdated);

        self::assertInstanceOf(Version::class, $returnedVersion = $this->package->getVersion('1.0.0'));
        self::assertEquals($id1, $returnedVersion->id()); // We don't update the ID
        self::assertEquals('1.0.0', $returnedVersion->version());
        self::assertEquals('newref', $returnedVersion->reference());
        self::assertEquals(5678, $returnedVersion->size());
        self::assertEquals($date, $returnedVersion->date());
    }

    public function testPackageAddSameVersion(): void
    {
        $version = new Version(Uuid::uuid4(), '1.0.0', 'someref', 1234, new \DateTimeImmutable());
        $this->package->addOrUpdateVersion($version);
        $this->package->addOrUpdateVersion($version); // this should not throw exception

        $this->expectException(\RuntimeException::class);
        $version->setPackage($this->package);
    }
}
