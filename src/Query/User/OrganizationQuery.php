<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User;

use Buddy\Repman\Query\User\Model\Organization;
use Munus\Control\Option;

interface OrganizationQuery
{
    /**
     * @return Option<Organization>
     */
    public function getByAlias(string $alias): Option;
}
