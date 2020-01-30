<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Organization;

use Buddy\Repman\Service\Organization\AliasGenerator;
use PHPUnit\Framework\TestCase;

final class AliasGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new AliasGenerator();
        $name = " - Test  ąęśźćńłó ĄĘŚŹĆŃŁÓ !@#$%^&*(){}[]:\";'\/`~|<>,.ÅåÄäÖöÆæØøÜü";

        self::assertEquals(
            'test-aeszcnlo-aeszcnlo-aaaaooaeaeoouu',
            $generator->generate($name)
        );
    }
}
