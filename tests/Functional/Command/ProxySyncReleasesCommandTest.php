<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ProxySyncReleasesCommand;
use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Buddy\Repman\Service\Stream;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Doctrine\DBAL\Connection;
use Munus\Control\Option;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\DoctrineDbalStore;

final class ProxySyncReleasesCommandTest extends FunctionalTestCase
{
    private string $basePath = __DIR__.'/../../Resources';
    private FilesystemAdapter $cache;
    private string $newDistPath = '/packagist.org/dist/buddy-works/repman/61e39aa8197cf1bc7fcb16a6f727b0c291bc9b76.zip';
    private string $feedPath = '/packagist.org/feed/releases.rss';

    public function testSyncReleases(): void
    {
        $newDist = $this->basePath.$this->newDistPath;
        $feed = (string) file_get_contents($this->basePath.$this->feedPath);
        @unlink($newDist);

        // cache miss (no pubDate)
        $command = $this->prepareCommand($feed);
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute([]);

        self::assertFileExists($newDist);
        self::assertEquals($result, 0);
        @unlink($newDist);

        // cache hit (pubDate is set)
        $command = $this->prepareCommand($feed, true);
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute([]);

        self::assertFileDoesNotExist($newDist);
        self::assertEquals($result, 0);
    }

    public function testParsingError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to parse RSS feed');

        $command = $this->prepareCommand('invalid xml');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    public function testJobLocking(): void
    {
        $newDist = $this->basePath.$this->newDistPath;
        $feed = (string) file_get_contents($this->basePath.$this->feedPath);
        @unlink($newDist);

        $command = $this->prepareCommand($feed, false, true);
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute([]);

        self::assertFileDoesNotExist($newDist);
        self::assertEquals($result, 0);
    }

    private function prepareCommand(string $feed, bool $fromCache = false, bool $lockCreated = false): ProxySyncReleasesCommand
    {
        $lockFactory = $lockCreated ? $this->fakeLockFactory() : $this->lockFactory();

        if (!$fromCache) {
            $this->cache()->delete('pub_date');
        }

        $feedDownloader = $this->createMock(Downloader::class);
        $feedDownloader->method('getContents')->willReturn(Option::of(Stream::fromString($feed)));

        return new ProxySyncReleasesCommand(
            $this->container()->get(ProxyRegister::class),
            $feedDownloader,
            $this->cache(),
            $lockFactory
        );
    }

    private function cache(): FilesystemAdapter
    {
        return $this->cache = $this->cache ?? new FilesystemAdapter('test', 0, self::$kernel->getCacheDir());
    }

    private function fakeLockFactory(): LockFactory
    {
        $fakeLock = $this->createMock(LockInterface::class);
        $fakeLock->method('acquire')->willReturn(false);

        $fakeLockFactory = $this->createMock(LockFactory::class);
        $fakeLockFactory->method('createLock')->willReturn($fakeLock);

        return $fakeLockFactory;
    }

    private function lockFactory(): LockFactory
    {
        /** @var Connection */
        $connection = self::$kernel->getContainer()->get('doctrine')->getConnection();

        return new LockFactory(new DoctrineDbalStore($connection));
    }
}
