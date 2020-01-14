<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Downloader;

use Buddy\Repman\Service\Downloader\NativeDownloader;
use Munus\Control\Option;
use PHPUnit\Framework\TestCase;

final class NativeDownloaderTest extends TestCase
{
    public function testSuccessDownload(): void
    {
        $packages = __DIR__.'/../../../Resources/packages.json';

        self::assertTrue(Option::some(file_get_contents($packages))->equals(
            (new NativeDownloader())->getContents($packages)
        ));
    }

    public function testFailedDownload(): void
    {
        self::assertTrue(Option::none()->equals(
            (new NativeDownloader())->getContents('/tmp/not-exists')
        ));
    }
}
