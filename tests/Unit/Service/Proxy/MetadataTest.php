<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Proxy;

use Buddy\Repman\Service\Proxy\Metadata;
use PHPUnit\Framework\TestCase;

final class MetadataTest extends TestCase
{
    public function testStreamCheck(): void
    {
        $this->expectException(\RuntimeException::class);

        Metadata::fromString('string', 'php://invalid');
    }
}
