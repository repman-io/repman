<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

use Buddy\Repman\Query\User\Model\Version;

interface VersionQuery
{
    /**
     * @return Version[]
     */
    public function findDevVersions(string $packageId): array;
}
