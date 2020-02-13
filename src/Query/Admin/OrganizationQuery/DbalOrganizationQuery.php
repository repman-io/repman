<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\OrganizationQuery;

use Buddy\Repman\Query\Admin\Model\Organization;
use Buddy\Repman\Query\Admin\OrganizationQuery;
use Doctrine\DBAL\Connection;

final class DbalOrganizationQuery implements OrganizationQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Organization[]
     */
    public function findAll(int $limit = 20, int $offset = 0): array
    {
        return array_map(function (array $data): Organization {
            return $this->hydrateOrganization($data);
        }, $this->connection->fetchAll(
            'SELECT o.id, o.name, o.alias, u.email owner_email, COUNT(p.id) packages_count
            FROM "organization" o
            JOIN "user" u ON u.id = o.owner_id
            LEFT JOIN "organization_package" p ON p.organization_id = o.id
            GROUP BY o.id, u.email
            ORDER BY o.alias
            LIMIT :limit OFFSET :offset',
            [
                ':limit' => $limit,
                ':offset' => $offset,
            ])
        );
    }

    public function count(): int
    {
        return (int) $this
            ->connection
            ->fetchColumn('SELECT COUNT(id) FROM "organization"');
    }

    /**
     * @param array<mixed> $data
     */
    private function hydrateOrganization(array $data): Organization
    {
        return new Organization(
            $data['id'],
            $data['name'],
            $data['alias'],
            $data['owner_email'],
            $data['packages_count'],
        );
    }
}
