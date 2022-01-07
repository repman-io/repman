<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User;

use Buddy\Repman\Entity\Organization\Hook;

interface HooksQuery
{
    /**
     * @return Hook[]
     */
    public function findAll(string $organizationId): array;
}
