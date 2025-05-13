<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Twig;

use Buddy\Repman\Service\Twig\DateExtension;
use DateTimeImmutable;
use DateTimeZone;
use Iterator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function date_default_timezone_get;

final class DateExtensionTest extends TestCase
{
    private DateExtension $extension;

    private Environment $env;

    private string $oldTz;

    protected function setUp(): void
    {
        $this->extension = new DateExtension(new TokenStorage());
        $this->env = new Environment(new ArrayLoader());

        $this->oldTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Warsaw');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->oldTz);
    }

    public function testGetFilters(): void
    {
        $this->assertSame('time_diff', $this->extension->getFilters()[0]->getName());
    }

    public function testGetFunctions(): void
    {
        $this->assertSame('gmt_offset', $this->extension->getFunctions()[0]->getName());
    }

    /**
     * @dataProvider timeDiffProvider
     */
    public function testTimeDiff(string $expected, DateTimeImmutable $dateTime, DateTimeImmutable $now): void
    {
        $this->assertSame($expected, $this->extension->diff($this->env, $dateTime, $now));
    }

    /**
     * @dataProvider dateTimeProvider
     */
    public function testDateTime(string $expected, DateTimeImmutable $dateTime): void
    {
        $this->assertSame($expected, $this->extension->dateTime($this->env, $dateTime));
    }

    /**
     * @dataProvider dateTimeUtcProvider
     */
    public function testDateTimeUtc(string $expected, DateTimeImmutable $dateTime): void
    {
        $this->assertSame($expected, $this->extension->dateTimeUtc($this->env, $dateTime));
    }

    public function testGmtOffset(): void
    {
        $dateTime = new DateTimeImmutable('2020-07-10 12:34:56');

        date_default_timezone_set('Europe/Warsaw');
        $this->extension = new DateExtension(new TokenStorage());
        $this->assertSame('GMT+02:00', $this->extension->gmtOffset($this->env, $dateTime));

        date_default_timezone_set('UTC');
        $this->extension = new DateExtension(new TokenStorage());
        $this->assertSame('GMT+00:00', $this->extension->gmtOffset($this->env, $dateTime));
    }

    /**
     * @return mixed[]
     */
    public function timeDiffProvider(): Iterator
    {
        $dateTime = new DateTimeImmutable();
        yield ['1 day ago', $dateTime->modify('-1 day'), $dateTime];
        yield ['in 6 hours', $dateTime->modify('+6 hours'), $dateTime];
        yield ['10 minutes ago', $dateTime->modify('-10 minutes'), $dateTime];
        yield ['5 seconds ago', $dateTime->modify('-5 seconds'), $dateTime];
        yield ['2 seconds ago', $dateTime->modify('-2 seconds'), $dateTime];
        yield ['just now', $dateTime, $dateTime];
    }

    /**
     * @return mixed[]
     */
    public function dateTimeProvider(): Iterator
    {
        $dateTime = new DateTimeImmutable('2020-01-02 12:34:56');
        yield ['2020-01-02 12:34:51', $dateTime->modify('-5 second')];
        yield ['2020-01-02 12:34:55', $dateTime->modify('-1 second')];
        yield ['2020-01-01 12:34:56', $dateTime->modify('-1 day')];
    }

    /**
     * @return mixed[]
     */
    public function dateTimeUtcProvider(): Iterator
    {
        $dateTime = new DateTimeImmutable(
            '2020-10-10 12:34:56',
            new DateTimeZone('UTC')
        );
        // GMT+02:00 (with DST)
        yield ['2020-10-10 14:34:51', $dateTime->modify('-5 second')];
        yield ['2020-10-10 14:34:55', $dateTime->modify('-1 second')];
        yield ['2020-10-09 14:34:56', $dateTime->modify('-1 day')];
        yield ['2020-09-10 14:34:56', $dateTime->modify('-1 month')];
    }
}
