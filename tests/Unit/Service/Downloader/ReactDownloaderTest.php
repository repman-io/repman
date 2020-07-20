<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Downloader;

use Buddy\Repman\Service\Downloader\ReactDownloader;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;

final class ReactDownloaderTest extends TestCase
{
    public function testSuccessDownload(): void
    {
        $packages = __DIR__.'/../../../Resources/packages.json';

        self::assertTrue(is_resource((new ReactDownloader())->getContents($packages)->getOrNull()));
    }

    public function testFailedDownload(): void
    {
        self::assertTrue(Option::none()->equals(
            (new ReactDownloader())->getContents('/tmp/not-exists')
        ));
    }

    public function testNotFoundHandler(): void
    {
        $this->expectException(\LogicException::class);

        (new ReactDownloader())->getContents('https://repman.io/not-exist', [], function (): void {throw new \LogicException('Not found'); });
    }

    public function testLastModified(): void
    {
        $downloader = new ReactDownloader();
        $downloader->getLastModified('https://repman.io', function (int $timestamp): void {
            self::assertTrue($timestamp > 0);
        });
        $downloader->getLastModified('/tmp/not-exists', function (int $timestamp): void {
            throw new \LogicException('Should not happen');
        });
        $downloader->run();
    }
}
