<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

use Buddy\Repman\Query\Admin\Model\Organization;

interface OrganizationQuery
{
    /**
     * @return Organization[]
     */
    public function findAll(int $limit = 20, int $offset = 0): array;

    public function count(): int;
}
