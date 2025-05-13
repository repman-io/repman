<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ScanAllPackagesCommand;
use Buddy\Repman\Service\Security\PackageScanner;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use Symfony\Component\Console\Tester\CommandTester;

final class ScanAllPackagesCommandTest extends FunctionalTestCase
{
    private string $buddyId;

    protected function setUp(): void
    {
        parent::setUp();

        $userId = $this->createAndLoginAdmin();
        $this->buddyId = $this->fixtures->createOrganization('buddy', $userId);
    }

    public function testScanSuccess(): void
    {
        $packageId = $this->fixtures->addPackage($this->buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new DateTimeImmutable());

        $commandTester = new CommandTester($this->container()->get(ScanAllPackagesCommand::class));
        $result = $commandTester->execute([]);

        $this->assertSame(0, $result);
        $this->assertTrue($this->container()->get(PackageScanner::class)->wasScanned($packageId));
    }
}
