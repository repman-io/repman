<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api;

use Buddy\Repman\Query\Api\Model\Package;
use Munus\Control\Option;

interface PackageQuery
{
    /**
     * @return Package[]
     */
    public function findAll(string $organizationId, int $limit = 20, int $offset = 0): array;

    public function count(string $organizationId): int;

    /**
     * @return Option<Package>
     */
    public function getById(string $organizationId, string $id): Option;
}
