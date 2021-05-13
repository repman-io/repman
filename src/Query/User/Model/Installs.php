<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use Buddy\Repman\Query\User\Model\Installs\Day;

final class Installs
{
    /**
     * @var Day[]
     */
    private array $days;

    private int $total;

    /**
     * @param Day[] $days
     */
    public function __construct(array $days, int $preriod, int $total)
    {
        $this->days = $this->addMissing($days, $preriod);
        $this->total = $total;
    }

    /**
     * @return Day[]
     */
    public function days(): array
    {
        return $this->days;
    }

    public function daysTotal(): int
    {
        return array_sum(array_map(fn (Day $day) => $day->installs(), $this->days));
    }

    public function total(): int
    {
        return $this->total;
    }

    /**
     * @param Day[] $days
     *
     * @return Day[]
     */
    private function addMissing(array $days, int $period): array
    {
        $all = [];
        foreach ($days as $day) {
            $all[$day->date()] = $day;
        }

        for ($i = 0; $i < $period; ++$i) {
            $date = (new \DateTimeImmutable())->modify(sprintf('-%s days', $i))->format('Y-m-d');
            if (!isset($all[$date])) {
                $all[$date] = new Day($date, 0);
            }
        }
        ksort($all);

        return array_values($all);
    }
}
