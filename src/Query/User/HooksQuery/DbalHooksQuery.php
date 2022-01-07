<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\HooksQuery;

use Buddy\Repman\Entity\Organization\Hook\Trigger;
use Buddy\Repman\Query\User\HooksQuery;
use Buddy\Repman\Query\User\Model\Hook;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

final class DbalHooksQuery implements HooksQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findAll(string $organizationId): array
    {
        return [];
    }
}
