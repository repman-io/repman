<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Proxy;

use Buddy\Repman\Query\Admin\Proxy\Model\Package;
use Buddy\Repman\Query\User\Model\Installs;

interface DownloadsQuery
{
    /**
     * @param string[] $names
     *
     * @return Package[]
     */
    public function findByNames(array $names): array;

    public function getInstalls(int $lastDays = 30): Installs;
}
