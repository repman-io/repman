<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ProxySyncMetadataCommand;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

final class ProxySyncMetadataCommandTest extends FunctionalTestCase
{
    private FakeDownloader $downloader;
    private LockFactory $lockFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->downloader = new FakeDownloader();
        $this->lockFactory = new LockFactory(new FlockStore());
    }

    public function testMetadataSynchronization(): void
    {
        $lock = $this->lockFactory->createLock(ProxySyncMetadataCommand::LOCK_NAME);
        self::assertFalse($lock->isAcquired());

        $commandTester = new CommandTester($this->prepareCommand());
        $result = $commandTester->execute([]);

        self::assertEquals($result, 0);

        // test if lock was released
        self::assertTrue($lock->acquire());
        $lock->release();
    }

    public function testJobLocking(): void
    {
        $lock = $this->lockFactory->createLock(ProxySyncMetadataCommand::LOCK_NAME);
        $lock->acquire();
        self::assertTrue($lock->isAcquired());

        $commandTester = new CommandTester($this->prepareCommand());
        $result = $commandTester->execute([]);

        self::assertEquals($result, 0);
        self::assertTrue($lock->isAcquired());
        $lock->release();
    }

    private function prepareCommand(): ProxySyncMetadataCommand
    {
        return new ProxySyncMetadataCommand(
            $this->container()->get(ProxyRegister::class),
            $this->downloader,
            $this->lockFactory
        );
    }
}
