<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Twig;

use Buddy\Repman\Service\Twig\BytesExtension;
use Iterator;
use PHPUnit\Framework\TestCase;

final class BytesExtensionTest extends TestCase
{
    private BytesExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new BytesExtension();
    }

    public function testGetFilters(): void
    {
        $this->assertSame('format_bytes', $this->extension->getFilters()[0]->getName());
    }

    /**
     * @dataProvider formatBytesProvider
     */
    public function testFormatBytes(string $expected, int $bytes, int $precision): void
    {
        $this->assertSame($expected, $this->extension->formatBytes($bytes, $precision));
    }

    /**
     * @return mixed[]
     */
    public function formatBytesProvider(): Iterator
    {
        yield ['1 KB', 1024, 0];
        yield ['1.0 KB', 1024, 1];
        yield ['1.00 KB', 1024, 2];
        yield ['1.23 KB', 1260, 2];
        yield ['5.75 MB', 6029312, 2];
        yield ['13.37 GB', 14355928186, 2];
    }
}
