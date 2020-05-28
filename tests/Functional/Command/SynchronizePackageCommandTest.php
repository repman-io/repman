<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\SynchronizePackageCommand;
use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

final class SynchronizePackageCommandTest extends FunctionalTestCase
{
    private string $userId;
    private string $buddyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = $this->createAndLoginAdmin();
        $this->buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
    }

    public function testSynchronizeSuccess(): void
    {
        $packageId = $this->fixtures->addPackage($this->buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new \DateTimeImmutable());

        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        $transport->reset();

        $commandTester = new CommandTester($this->container()->get(SynchronizePackageCommand::class));
        $result = $commandTester->execute([
            'packageId' => $packageId,
        ]);

        self::assertCount(1, $transport->getSent());
        self::assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());
        self::assertEquals($result, 0);
    }

    public function testPackageNotFound(): void
    {
        $commandTester = new CommandTester($this->container()->get(SynchronizePackageCommand::class));
        $result = $commandTester->execute([
            'packageId' => 'c0dbfca1-cf1b-4334-9081-41a2125fc443',
        ]);

        self::assertStringContainsString('Package not found', $commandTester->getDisplay());
        self::assertEquals($result, 1);
    }
}
