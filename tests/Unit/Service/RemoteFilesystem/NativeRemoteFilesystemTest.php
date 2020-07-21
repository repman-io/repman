<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\RemoteFilesystem;

use Buddy\Repman\Service\Downloader\ReactDownloader;
use PHPUnit\Framework\TestCase;

final class NativeRemoteFilesystemTest extends TestCase
{
    public function testCorrectDownload(): void
    {
        $rf = new ReactDownloader();

        self::assertFalse($rf->getContents(__FILE__)->isEmpty());
    }

    public function testInCorrectDownload(): void
    {
        $rf = new ReactDownloader();

        self::assertTrue($rf->getContents('/not/exits')->isEmpty());
    }
}
