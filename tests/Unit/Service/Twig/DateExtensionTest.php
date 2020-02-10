<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Twig;

use Buddy\Repman\Service\Twig\DateExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class DateExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        $extension = new DateExtension();

        self::assertEquals('time_diff', $extension->getFilters()[0]->getName());
    }

    /**
     * @dataProvider timeDiffProvider
     */
    public function testTimeDiff(string $excpeted, \DateTimeImmutable $dateTime): void
    {
        $extension = new DateExtension();
        $env = new Environment(new ArrayLoader());

        self::assertEquals($excpeted, $extension->diff($env, $dateTime));
    }

    /**
     * @return mixed[]
     */
    public function timeDiffProvider(): array
    {
        $dateTime = new \DateTimeImmutable();

        return [
            ['5 seconds ago', $dateTime->modify('-5 second')],
            ['1 second ago', $dateTime->modify('-1 second')],
            ['1 day ago', $dateTime->modify('-1 day')],
        ];
    }
}
