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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

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
    ];

    private string $timezone;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->timezone = \date_default_timezone_get();

        if (($token = $tokenStorage->getToken()) === null) {
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
            new TwigFilter('time_diff', [$this, 'diff'], ['needs_environment' => true]),
            new TwigFilter('date_time', [$this, 'dateTime'], ['needs_environment' => true]),
        ];
    }

    /**
     * Filters for converting dates to a time ago string like Facebook and Twitter has.
     *
     * @param string|\DateTimeInterface $date a string or DateTime object to convert
     * @param string|\DateTimeInterface $now  A string or DateTime object to compare with. If none given, the current time will be used.
     *
     * @return string the converted time
     */
    public function diff(Environment $env, $date, $now = null): string
    {
        // Convert both dates to DateTime instances.
        $date = twig_date_converter($env, $date, $this->timezone);
        $now = twig_date_converter($env, $now, $this->timezone);

        // Get the difference between the two DateTime objects.
        $diff = $date->diff($now);

        // Check for each interval if it appears in the $diff object.
        foreach (self::$units as $attribute => $unit) {
            $count = $diff->$attribute;

            if (0 !== $count) {
                return $this->getPluralizedInterval($count, $diff->invert, $unit);
            }
        }

        return '';
    }

    /**
     * @param string|\DateTimeInterface $date
     */
    public function dateTime(Environment $env, $date): string
    {
        return twig_date_converter($env, $date, $this->timezone)->format('Y-m-d H:i:s');
    }

    private function getPluralizedInterval(int $count, int $invert, string $unit): string
    {
        if (1 !== $count) {
            $unit .= 's';
        }

        return $invert ? "in $count $unit" : "$count $unit ago";
    }
}
