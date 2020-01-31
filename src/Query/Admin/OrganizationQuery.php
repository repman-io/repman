<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

use Buddy\Repman\Query\Admin\Model\Organization;
use Munus\Control\Option;

interface OrganizationQuery
{
    /**
     * @return Option<Organization>
     */
    public function getByAlias(string $alias): Option;
}
