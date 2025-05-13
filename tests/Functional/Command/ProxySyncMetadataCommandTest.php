<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ProxySyncMetadataCommand;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

final class ProxySyncMetadataCommandTest extends FunctionalTestCase
{
    private LockFactory $lockFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockFactory = new LockFactory(new FlockStore());
    }

    public function testMetadataSynchronization(): void
    {
        $lock = $this->lockFactory->createLock(ProxySyncMetadataCommand::LOCK_NAME);
        $this->assertFalse($lock->isAcquired());

        $commandTester = new CommandTester($this->prepareCommand());
        $result = $commandTester->execute([]);

        $this->assertSame(0, $result);

        // test if lock was released
        $this->assertTrue($lock->acquire());
        $lock->release();
    }

    public function testJobLocking(): void
    {
        $lock = $this->lockFactory->createLock(ProxySyncMetadataCommand::LOCK_NAME);
        $lock->acquire();
        $this->assertTrue($lock->isAcquired());

        $commandTester = new CommandTester($this->prepareCommand());
        $result = $commandTester->execute([]);

        $this->assertSame(0, $result);
        $this->assertTrue($lock->isAcquired());
        $lock->release();
    }

    private function prepareCommand(): ProxySyncMetadataCommand
    {
        return new ProxySyncMetadataCommand(
            $this->container()->get(ProxyRegister::class),
            $this->lockFactory
        );
    }
}
