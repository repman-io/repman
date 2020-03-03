<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\OrganizationQuery;

use Buddy\Repman\Query\User\Model\Installs;
use Buddy\Repman\Query\User\Model\Organization;
use Buddy\Repman\Query\User\Model\Organization\Token;
use Buddy\Repman\Query\User\OrganizationQuery;
use Doctrine\DBAL\Connection;
use Munus\Control\Option;

final class DbalOrganizationQuery implements OrganizationQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Option<Organization>
     */
    public function getByAlias(string $alias): Option
    {
        $data = $this->connection->fetchAssoc(
            'SELECT id, name, alias, owner_id FROM "organization" WHERE alias = :alias', [
            ':alias' => $alias,
        ]);

        if ($data === false) {
            return Option::none();
        }

        $data['token'] = $this->connection->fetchColumn('SELECT value FROM organization_token WHERE organization_id = :id', [
            ':id' => $data['id'],
        ]);

        return Option::some($this->hydrateOrganization($data));
    }

    /**
     * @return Token[]
     */
    public function findAllTokens(string $organizationId, int $limit = 20, int $offset = 0): array
    {
        return array_map(function (array $data): Token {
            return new Token(
                $data['name'],
                $data['value'],
                new \DateTimeImmutable($data['created_at']),
                $data['last_used_at'] !== null ? new \DateTimeImmutable($data['last_used_at']) : null
            );
        }, $this->connection->fetchAll('
            SELECT name, value, created_at, last_used_at 
            FROM organization_token 
            WHERE organization_id = :id
            ORDER BY UPPER(name) ASC
            LIMIT :limit OFFSET :offset', [
            ':id' => $organizationId,
            ':limit' => $limit,
            ':offset' => $offset,
        ]));
    }

    public function tokenCount(string $organizationId): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(value) FROM organization_token WHERE organization_id = :id',
                [':id' => $organizationId]
            );
    }

    public function getInstalls(string $organizationId, int $lastDays = 30): Installs
    {
        $packagesId = array_column($this->connection->fetchAll('SELECT id FROM organization_package WHERE organization_id = :id', [':id' => $organizationId]), 'id');

        return new Installs(
            array_map(function (array $row): Installs\Day {
                return new Installs\Day($row['date'], $row['count']);
            }, $this->connection->fetchAll('SELECT * FROM (SELECT COUNT(package_id), date FROM organization_package_download WHERE date > :date AND package_id IN (:packages) GROUP BY date) AS installs ORDER BY date ASC', [
                ':date' => (new \DateTimeImmutable())->modify(sprintf('-%s days', $lastDays))->format('Y-m-d'),
                ':packages' => $packagesId,
            ], [':packages' => Connection::PARAM_STR_ARRAY])),
            $lastDays,
            (int) $this->connection->fetchColumn('SELECT COUNT(package_id) FROM organization_package_download WHERE package_id IN (:packages)', [':packages' => $packagesId], 0, [':packages' => Connection::PARAM_STR_ARRAY])
        );
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
            $data['owner_id'],
            $data['token'] !== false ? $data['token'] : null
        );
    }
}
