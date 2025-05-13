<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\ConfigQuery;

use Buddy\Repman\Query\Admin\ConfigQuery;
use Doctrine\DBAL\Connection;

final class DbalConfigQuery implements ConfigQuery
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array<string,string>
     */
    public function findAll(): array
    {
        $data = $this->connection->fetchAllAssociative('SELECT key, value FROM config');
        $values = [];
        foreach ($data as $row) {
            $values[(string) $row['key']] = $row['value'];
        }

        return $values;
    }
}
