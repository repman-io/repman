<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service;

use Buddy\Repman\Service\Stream;
use PHPUnit\Framework\TestCase;

final class StreamTest extends TestCase
{
    public function testStreamCheck(): void
    {
        $this->expectException(\RuntimeException::class);

        Stream::fromString('string', 'php://invalid');
    }
}
