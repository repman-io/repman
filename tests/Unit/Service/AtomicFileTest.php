<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\AtomicFile;
use PHPUnit\Framework\TestCase;

final class AtomicFileTest extends TestCase
{
    public function testWrite(): void
    {
        $filename = sys_get_temp_dir().'/example.txt';
        @unlink($filename);

        AtomicFile::write($filename, 'content');

        self::assertEquals('content', file_get_contents($filename));
    }
}
