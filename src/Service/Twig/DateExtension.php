<?php

declare(strict_types=1);

/**
 * This file is part of Twig.
 *
 * (c) 2014-2019 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Buddy\Repman\Service\Twig;

use Buddy\Repman\Security\Model\User;
use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Component\Intl\Timezones;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @author Robin van der Vleuten <robinvdvleuten@gmail.com>
 */
class DateExtension extends AbstractExtension
{
    /**
     * @var array<string,string>
     */
    public static $units = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
        'f' => 'μs',
    ];

    private string $timezone;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->timezone = date_default_timezone_get();

        if (!($token = $tokenStorage->getToken()) instanceof TokenInterface) {
            return;
        }

        if (($user = $token->getUser()) instanceof User) {
            $this->timezone = $user->timezone();
        }
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('time_diff', $this->diff(...), ['needs_environment' => true]),
            new TwigFilter('date_time', $this->dateTime(...), ['needs_environment' => true]),
            new TwigFilter('date_time_utc', $this->dateTimeUtc(...), ['needs_environment' => true]),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('gmt_offset', $this->gmtOffset(...), ['needs_environment' => true]),
        ];
    }

    /**
     * @param string|DateTimeInterface $date a string or DateTime object to convert
     * @param string|DateTimeInterface $now  A string or DateTime object to compare with. If none given, the current time will be used.
     *
     * @throws DateMalformedStringException
     *
     * @return string the converted time
     */
    public function diff(Environment $env, $date, $now = null): string
    {
        $date = $env->getExtension(CoreExtension::class)->convertDate($date);

        $now = $now === null
            ? new DateTimeImmutable(
                $env->getExtension(CoreExtension::class)->convertDate(timezone: $this->timezone)->format('Y-m-d H:i:s')
            )
            : $env->getExtension(CoreExtension::class)->convertDate($now);

        // Get the difference between the two DateTime objects.
        $diff = $date->diff($now);

        // Check for each interval if it appears in the $diff object.
        foreach (self::$units as $attribute => $unit) {
            $count = $diff->$attribute;

            if (0 !== $count) {
                if ($attribute === 'f') {
                    return 'just now';
                }

                return $this->getPluralizedInterval($count, $diff->invert, $unit);
            }
        }

        return '';
    }

    /**
     * @param string|DateTimeInterface $date
     *
     * @throws DateInvalidTimeZoneException
     */
    public function dateTime(Environment $env, $date, ?string $sourceTimezone = null): string
    {
        $date = $sourceTimezone === null
            ? $env->getExtension(CoreExtension::class)->convertDate($date, $this->timezone)
            : (new DateTimeImmutable(
                $env->getExtension(CoreExtension::class)->convertDate($date)->format('Y-m-d H:i:s'),
                new DateTimeZone($sourceTimezone)
            ))->setTimezone(new DateTimeZone($this->timezone));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param string|DateTimeInterface $date
     *
     * @throws DateInvalidTimeZoneException
     */
    public function dateTimeUtc(Environment $env, $date): string
    {
        return $this->dateTime($env, $date, 'UTC');
    }

    /**
     * @param string|DateTimeInterface $now A string or DateTime object. If none given, the current time will be used.
     */
    public function gmtOffset(Environment $env, $now = null): string
    {
        $now = $now === null
            ? new DateTimeImmutable(
                $env->getExtension(CoreExtension::class)->convertDate(timezone: $this->timezone)->format('Y-m-d H:i:s')
            )
            : $env->getExtension(CoreExtension::class)->convertDate($now);

        return Timezones::getGmtOffset($this->timezone, $now->getTimestamp());
    }

    private function getPluralizedInterval(int $count, int $invert, string $unit): string
    {
        if (1 !== $count) {
            $unit .= 's';
        }

        return $invert === 1 ? sprintf('in %d %s', $count, $unit) : sprintf('%d %s ago', $count, $unit);
    }
}
