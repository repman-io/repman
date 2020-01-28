<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\UserQuery;

use Buddy\Repman\Query\Admin\Model\User;
use Buddy\Repman\Query\Admin\UserQuery;
use Doctrine\DBAL\Connection;

final class DbalUserQuery implements UserQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findAll(int $limit = 20, int $offset = 0): array
    {
        return array_map(function (array $data): User {
            return new User(
                $data['id'],
                $data['email'],
                json_decode($data['roles'])
            );
        }, $this->connection->fetchAll('SELECT id, email, roles FROM "user" LIMIT :limit OFFSET :offset', [
            ':limit' => $limit,
            ':offset' => $offset,
        ]));
    }

    public function count(): int
    {
        return (int) $this->connection->fetchColumn('SELECT COUNT(id) FROM "user"');
    }
}
