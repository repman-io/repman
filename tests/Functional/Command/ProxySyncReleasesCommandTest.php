<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ProxySyncReleasesCommand;
use Buddy\Repman\Service\Cache\InMemoryCache;
use Buddy\Repman\Service\Dist\Storage\FileStorage;
use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Proxy\MetadataProvider\CacheableMetadataProvider;
use Buddy\Repman\Service\Proxy\ProxyFactory;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Munus\Control\Option;
use Symfony\Component\Console\Tester\CommandTester;

final class ProxySyncReleasesCommandTest extends FunctionalTestCase
{
    private string $basePath = __DIR__.'/../../Resources';

    public function testSyncReleases(): void
    {
        $newDist = $this->basePath.'/packagist.org/dist/buddy-works/repman/1.2.3.0_5e77ad71826b9411cb873c0947a7d541d822dff1.zip';
        @unlink($newDist);

        $feed = (string) file_get_contents($this->basePath.'/packagist.org/feed/releases.rss');

        $command = $this->prepareCommand($feed);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertTrue(file_exists($newDist));
        @unlink($newDist);
    }

    public function testParsingError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to parse RSS feed');

        $command = $this->prepareCommand('invalid xml');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    private function prepareCommand(string $feed): ProxySyncReleasesCommand
    {
        $feedDownloader = $this->createMock(Downloader::class);
        $feedDownloader->method('getContents')->willReturn(Option::of($feed));

        $storageDownloader = $this->createMock(Downloader::class);
        $storageDownloader->method('getContents')->willReturn(Option::of('test'));

        return new ProxySyncReleasesCommand(
            new ProxyRegister(
                new ProxyFactory(
                    new CacheableMetadataProvider(new FakeDownloader(), new InMemoryCache()),
                    new FileStorage($this->basePath, $storageDownloader)
                )
            ),
            $feedDownloader
        );
    }
}
