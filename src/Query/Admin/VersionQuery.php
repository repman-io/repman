<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

interface VersionQuery
{
    public function oldDistsCount(int $daysOld): int;

    /**
     * @return array<array<string,string>>
     */
    public function findOldDists(int $daysOld = 30, int $limit = 100, int $offset = 0): array;
}
