<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User;

use Buddy\Repman\Query\User\Model\Installs;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Token;
use Munus\Control\Option;

interface OrganizationQuery
{
    /**
     * @return Option<Organization>
     */
    public function getByAlias(string $alias): Option;

    /**
     * @return Token[]
     */
    public function findAllTokens(string $organizationId, int $limit = 20, int $offset = 0): array;

    public function tokenCount(string $organizationId): int;

    public function getInstalls(string $organizationId, int $lastDays = 30): Installs;
}
