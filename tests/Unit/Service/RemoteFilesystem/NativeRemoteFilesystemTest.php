<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\RemoteFilesystem;

use Buddy\Repman\Service\Downloader\NativeDownloader;
use PHPUnit\Framework\TestCase;

final class NativeRemoteFilesystemTest extends TestCase
{
    public function testCorrectDownload(): void
    {
        $rf = new NativeDownloader();

        self::assertFalse($rf->getContents(__FILE__)->isEmpty());
    }

    public function testInCorrectDownload(): void
    {
        $rf = new NativeDownloader();

        self::assertTrue($rf->getContents('/not/exits')->isEmpty());
    }
}
