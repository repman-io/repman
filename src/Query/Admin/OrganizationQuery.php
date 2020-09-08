<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

use Buddy\Repman\Query\Admin\Model\Organization;
use Buddy\Repman\Query\Filter;
use Buddy\Repman\Query\User\Model\Installs;

interface OrganizationQuery
{
    /**
     * @return Organization[]
     */
    public function findAll(Filter $filter): array;

    public function count(): int;

    public function getInstalls(int $lastDays = 30): Installs;
}
