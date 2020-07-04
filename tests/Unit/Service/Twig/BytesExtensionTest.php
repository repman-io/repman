<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Twig;

use Buddy\Repman\Service\Twig\BytesExtension;
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
        self::assertEquals('format_bytes', $this->extension->getFilters()[0]->getName());
    }

    /**
     * @dataProvider formatBytesProvider
     */
    public function testFormatBytes(string $expected, int $bytes, int $precision): void
    {
        self::assertEquals($expected, $this->extension->formatBytes($bytes, $precision));
    }

    /**
     * @return mixed[]
     */
    public function formatBytesProvider(): array
    {
        return [
            ['1 KB', 1024, 0],
            ['1.0 KB', 1024, 1],
            ['1.00 KB', 1024, 2],
            ['1.23 KB', 1260, 2],
            ['5.75 MB', 6029312, 2],
            ['13.37 GB', 14355928186, 2],
        ];
    }
}
