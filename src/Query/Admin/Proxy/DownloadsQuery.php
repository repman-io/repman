<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\Proxy;

use Buddy\Repman\Query\Admin\Proxy\Model\Package;

interface DownloadsQuery
{
    /**
     * @param string[] $names
     *
     * @return Package[]
     */
    public function findByNames(array $names): array;
}
