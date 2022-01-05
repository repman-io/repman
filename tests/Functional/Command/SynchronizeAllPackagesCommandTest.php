<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\SynchronizeAllPackagesCommand;
use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

final class SynchronizeAllPackagesCommandTest extends FunctionalTestCase
{
    private string $buddyId;

    protected function setUp(): void
    {
        parent::setUp();

        $userId = $this->createAndLoginAdmin();
        $this->buddyId = $this->fixtures->createOrganization('buddy', $userId);

        $packageId = $this->fixtures->addPackage($this->buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new \DateTimeImmutable());
    }

    public function testSynchronizeAllSuccess(): void
    {
        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        $transport->reset();

        $commandTester = new CommandTester($this->container()->get(SynchronizeAllPackagesCommand::class));
        $result = $commandTester->execute([]);

        self::assertCount(1, $transport->getSent());
        self::assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());
        self::assertEquals($result, 0);
    }

    public function testSynchronizeAllOrganizationSuccess(): void
    {
        $alias = 'buddy';
        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        $transport->reset();

        $commandTester = new CommandTester($this->container()->get(SynchronizeAllPackagesCommand::class));
        $result = $commandTester->execute(['organization' => $alias]);

        self::assertCount(1, $transport->getSent());
        self::assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());
        self::assertEquals($result, 0);
    }

    public function testSynchronizeAllOrganizationFailure(): void
    {
        $alias = 'non-existing-alias';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Organization with alias %s not found.', $alias));

        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        $transport->reset();

        $commandTester = new CommandTester($this->container()->get(SynchronizeAllPackagesCommand::class));
        $commandTester->execute(['organization' => $alias]);
    }
}
