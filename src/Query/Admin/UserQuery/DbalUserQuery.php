<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\UserQuery;

use Buddy\Repman\Query\Admin\Model\User;
use Buddy\Repman\Query\Admin\UserQuery;
use Doctrine\DBAL\Connection;
use Munus\Control\Option;

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
            return $this->hydrateUser($data);
        }, $this->connection->fetchAll('SELECT id, email, status, roles FROM "user" ORDER BY email LIMIT :limit OFFSET :offset', [
            ':limit' => $limit,
            ':offset' => $offset,
        ]));
    }

    /**
     * @return Option<User>
     */
    public function getByEmail(string $email): Option
    {
        $data = $this->connection->fetchAssoc('SELECT id, email, status, roles FROM "user" WHERE email = :email', [
            ':email' => \mb_strtolower($email),
        ]);
        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydrateUser($data));
    }

    /**
     * @return Option<User>
     */
    public function getById(string $id): Option
    {
        $data = $this->connection->fetchAssoc('SELECT id, email, status, roles FROM "user" WHERE id = :id', [
            ':id' => $id,
        ]);
        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydrateUser($data));
    }

    public function count(): int
    {
        return (int) $this->connection->fetchColumn('SELECT COUNT(id) FROM "user"');
    }

    /**
     * @param array<mixed> $data
     */
    private function hydrateUser(array $data): User
    {
        return new User(
            $data['id'],
            $data['email'],
            $data['status'],
            json_decode($data['roles'])
        );
    }
}
