<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\RemoteFilesystem;

use Buddy\Repman\Service\RemoteFilesystem\NativeRemoteFilesystem;
use PHPUnit\Framework\TestCase;

final class NativeRemoteFilesystemTest extends TestCase
{
    public function testCorrectDownload(): void
    {
        $rf = new NativeRemoteFilesystem();

        self::assertFalse($rf->getContents(__FILE__)->isEmpty());
    }

    public function testInCorrectDownload(): void
    {
        $rf = new NativeRemoteFilesystem();

        self::assertTrue($rf->getContents('/not/exits')->isEmpty());
    }
}
