<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization\TokenGenerator;

use Buddy\Repman\Service\Organization\TokenGenerator\RandomTokenGenerator;
use PHPUnit\Framework\TestCase;

final class RandomTokenGeneratorTest extends TestCase
{
    public function testRandomTokenGenerator(): void
    {
        $generator = new RandomTokenGenerator();

        self::assertNotEquals($generator->generate(), $generator->generate());
    }
}
