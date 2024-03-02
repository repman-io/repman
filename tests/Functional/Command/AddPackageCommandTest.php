<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\AddPackageCommand;
use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

final class AddPackageCommandTest extends FunctionalTestCase
{
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = $this->createAndLoginAdmin();
        $this->fixtures->createOrganization('buddy', $this->userId);
    }

    public function testAddSuccess(): void
    {
        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        $transport->reset();

        $commandTester = new CommandTester($this->container()->get(AddPackageCommand::class));
        $result = $commandTester->execute([
            'organization' => 'buddy',
            'type' => 'artifact',
            'url' => '/path/to/package',
        ]);

        self::assertEquals($result, 0);
        self::assertCount(1, $transport->getSent());
        self::assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());
    }

    public function testInvalidOrganization(): void
    {
        $commandTester = new CommandTester($this->container()->get(AddPackageCommand::class));
        $result = $commandTester->execute([
            'organization' => 'vendor',
            'type' => 'artifact',
            'url' => '/path/to/package',
        ]);

        self::assertEquals($result, 1);
    }

    public function testInvalidType(): void
    {
        $commandTester = new CommandTester($this->container()->get(AddPackageCommand::class));
        $result = $commandTester->execute([
            'organization' => 'vendor',
            'type' => 'vcs',
            'url' => '/path/to/package',
        ]);

        self::assertEquals($result, 1);
    }
}
