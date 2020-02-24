<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\PackageQuery;

use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Query\User\PackageQuery;
use Doctrine\DBAL\Connection;
use Munus\Control\Option;

final class DbalPackageQuery implements PackageQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Package[]
     */
    public function findAll(string $organizationId, int $limit = 20, int $offset = 0): array
    {
        return array_map(function (array $data): Package {
            return $this->hydratePackage($data);
        }, $this->connection->fetchAll(
            'SELECT id, repository_url, name, latest_released_version, latest_release_date, description, last_sync_at, last_sync_error
            FROM "organization_package"
            WHERE organization_id = :organization_id
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset', [
                ':organization_id' => $organizationId,
                ':limit' => $limit,
                ':offset' => $offset,
            ]));
    }

    /**
     * @return PackageName[]
     */
    public function getAllNames(string $organizationId): array
    {
        return array_map(function (array $data): PackageName {
            return new PackageName($data['id'], $data['name']);
        }, $this->connection->fetchAll(
            'SELECT id, name
            FROM "organization_package"
            WHERE organization_id = :organization_id AND name IS NOT NULL',
            [
            ':organization_id' => $organizationId,
        ]));
    }

    public function count(string $organizationId): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(id) FROM "organization_package"
                WHERE organization_id = :organization_id',
                [
                    ':organization_id' => $organizationId,
                ]
            );
    }

    /**
     * @return Option<Package>
     */
    public function getById(string $id): Option
    {
        $data = $this->connection->fetchAssoc(
            'SELECT id, repository_url, name, latest_released_version, latest_release_date, description, last_sync_at, last_sync_error
            FROM "organization_package" WHERE id = :id', [
            ':id' => $id,
        ]);
        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydratePackage($data));
    }

    /**
     * @param array<mixed> $data
     */
    private function hydratePackage(array $data): Package
    {
        return new Package(
            $data['id'],
            $data['repository_url'],
            $data['name'],
            $data['latest_released_version'],
            $data['latest_release_date'] ? new \DateTimeImmutable($data['latest_release_date']) : null,
            $data['description'],
            $data['last_sync_at'] ? new \DateTimeImmutable($data['last_sync_at']) : null,
            $data['last_sync_error']
        );
    }
}
