<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ScanAllPackagesCommand;
use Buddy\Repman\Command\UpdateAdvisoriesDbCommand;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Security\PackageScanner;
use Buddy\Repman\Tests\Doubles\FakeSecurityChecker;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class UpdateAdvisoriesDbCommandTest extends FunctionalTestCase
{
    private string $userId;
    private string $buddyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = $this->createAndLoginAdmin();
        $this->buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
    }

    public function testUpdate(): void
    {
        $packageId = $this->fixtures->addPackage($this->buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new \DateTimeImmutable());

        $scannerMock = $this->createMock(PackageScanner::class);
        $scannerMock
            ->expects(self::once())
            ->method('scan');

        $scanCommand = new ScanAllPackagesCommand(
            $scannerMock,
            self::$kernel->getContainer()->get('test.service_container')->get(PackageQuery::class),
            self::$kernel->getContainer()->get('test.service_container')->get(PackageRepository::class)
        );

        $command = new UpdateAdvisoriesDbCommand(
            new FakeSecurityChecker(),
            $scanCommand
        );

        $commandTester = new CommandTester($command);
        $result = $commandTester->execute([]);

        self::assertEquals($result, 0);
    }
}
