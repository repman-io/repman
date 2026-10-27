<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Unit\Service\Twig;

use Buddy\Repman\Security\Model\User;
use Buddy\Repman\Service\Twig\DateExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class DateExtensionTest extends TestCase
{
    private DateExtension $extension;
    private Environment $env;
    private string $oldTz;

    protected function setUp(): void
    {
        $this->extension = new DateExtension(new TokenStorage());
        $this->env = new Environment(new ArrayLoader());

        $this->oldTz = \date_default_timezone_get();
        date_default_timezone_set('Europe/Warsaw');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->oldTz);
    }

    public function testDoesNotReadTokenWhenConstructed(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->never())->method('getToken');

        self::assertNotSame([], (new DateExtension($tokenStorage))->getFilters());
    }

    public function testUsesTimezoneOfCurrentUser(): void
    {
        $tokenStorage = new TokenStorage();
        $extension = new DateExtension($tokenStorage);
        $date = new \DateTimeImmutable('2020-01-01 12:00:00', new \DateTimeZone('UTC'));

        self::assertSame('2020-01-01 13:00:00', $extension->dateTimeUtc($this->env, $date));
        // a zone-less string from the database is UTC, and converted once only
        self::assertSame('2020-01-01 13:00:00', $extension->dateTimeUtc($this->env, '2020-01-01 12:00:00'));

        $tokenStorage->setToken(new UsernamePasswordToken(self::user('Asia/Tokyo'), 'main', ['ROLE_USER']));
        self::assertSame('2020-01-01 21:00:00', $extension->dateTimeUtc($this->env, $date));

        // a worker rendering for the next user must not keep the previous one's timezone
        $tokenStorage->setToken(new UsernamePasswordToken(self::user('America/New_York'), 'main', ['ROLE_USER']));
        self::assertSame('2020-01-01 07:00:00', $extension->dateTimeUtc($this->env, $date));
    }

    public function testGetFilters(): void
    {
        self::assertSame('time_diff', $this->extension->getFilters()[0]->getName());
    }

    public function testGetFunctions(): void
    {
        self::assertSame('gmt_offset', $this->extension->getFunctions()[0]->getName());
    }

    #[DataProvider('timeDiffProvider')]
    public function testTimeDiff(string $expected, \DateTimeImmutable $dateTime, \DateTimeImmutable $now): void
    {
        self::assertSame($expected, $this->extension->diff($this->env, $dateTime, $now));
    }

    #[DataProvider('dateTimeProvider')]
    public function testDateTime(string $expected, \DateTimeImmutable $dateTime): void
    {
        self::assertSame($expected, $this->extension->dateTime($this->env, $dateTime));
    }

    #[DataProvider('dateTimeUtcProvider')]
    public function testDateTimeUtc(string $expected, \DateTimeImmutable $dateTime): void
    {
        self::assertSame($expected, $this->extension->dateTimeUtc($this->env, $dateTime));
    }

    public function testGmtOffset(): void
    {
        $dateTime = new \DateTimeImmutable('2020-07-10 12:34:56');

        date_default_timezone_set('Europe/Warsaw');
        $this->extension = new DateExtension(new TokenStorage());
        self::assertSame('GMT+02:00', $this->extension->gmtOffset($this->env, $dateTime));

        date_default_timezone_set('UTC');
        $this->extension = new DateExtension(new TokenStorage());
        self::assertSame('GMT+00:00', $this->extension->gmtOffset($this->env, $dateTime));
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function timeDiffProvider(): \Iterator
    {
        $dateTime = new \DateTimeImmutable();
        yield ['1 day ago', $dateTime->modify('-1 day'), $dateTime];
        yield ['in 6 hours', $dateTime->modify('+6 hours'), $dateTime];
        yield ['10 minutes ago', $dateTime->modify('-10 minutes'), $dateTime];
        yield ['5 seconds ago', $dateTime->modify('-5 seconds'), $dateTime];
        yield ['2 seconds ago', $dateTime->modify('-2 seconds'), $dateTime];
        yield ['just now', $dateTime, $dateTime];
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function dateTimeProvider(): \Iterator
    {
        $dateTime = new \DateTimeImmutable('2020-01-02 12:34:56', new \DateTimeZone('UTC'));
        // shown in the default timezone, Europe/Warsaw (GMT+01:00 in January)
        yield ['2020-01-02 13:34:51', $dateTime->modify('-5 second')];
        yield ['2020-01-02 13:34:55', $dateTime->modify('-1 second')];
        yield ['2020-01-01 13:34:56', $dateTime->modify('-1 day')];
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function dateTimeUtcProvider(): \Iterator
    {
        $dateTime = new \DateTimeImmutable(
            '2020-10-10 12:34:56',
            new \DateTimeZone('UTC')
        );
        // GMT+02:00 (with DST)
        yield ['2020-10-10 14:34:51', $dateTime->modify('-5 second')];
        yield ['2020-10-10 14:34:55', $dateTime->modify('-1 second')];
        yield ['2020-10-09 14:34:56', $dateTime->modify('-1 day')];
        yield ['2020-09-10 14:34:56', $dateTime->modify('-1 month')];
    }

    private static function user(string $timezone): User
    {
        return new User('a4b1c2d3-0000-4000-8000-000000000001', 'tz@repman.io', 'secret', 'enabled', true, '', ['ROLE_USER'], [], true, $timezone);
    }
}
