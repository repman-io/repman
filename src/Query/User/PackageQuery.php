<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User;

use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\Model\PackageName;
use Munus\Control\Option;

interface PackageQuery
{
    /**
     * @return Package[]
     */
    public function findAll(string $organizationId, int $limit = 20, int $offset = 0): array;

    /**
     * @return PackageName[]
     */
    public function getAllNames(string $organizationId): array;

    public function count(string $organizationId): int;

    /**
     * @return Option<Package>
     */
    public function getById(string $id): Option;
}
