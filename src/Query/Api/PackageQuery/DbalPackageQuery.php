<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\PackageQuery;

use Buddy\Repman\Query\Api\Model\Package;
use Buddy\Repman\Query\Api\PackageQuery;
use Buddy\Repman\Query\User\Model\ScanResult;
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
        }, $this->connection->fetchAllAssociative(
            'SELECT
                id,
                type,
                repository_url,
                name,
                latest_released_version,
                latest_release_date,
                description,
                last_sync_at,
                last_sync_error,
                webhook_created_at,
                last_scan_date,
                last_scan_status,
                last_scan_result,
                keep_last_releases
            FROM organization_package
            WHERE organization_id = :organization_id
            GROUP BY id
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset', [
                'organization_id' => $organizationId,
                'limit' => $limit,
                'offset' => $offset,
            ]));
    }

    public function count(string $organizationId): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(id) FROM "organization_package"
                WHERE organization_id = :organization_id',
                [
                    'organization_id' => $organizationId,
                ]
            );
    }

    /**
     * @return Option<Package>
     */
    public function getById(string $organizationId, string $id): Option
    {
        $data = $this->connection->fetchAssociative(
            'SELECT
                id,
                type,
                repository_url,
                name,
                latest_released_version,
                latest_release_date,
                description,
                last_sync_at,
                last_sync_error,
                webhook_created_at,
                last_scan_date,
                last_scan_status,
                last_scan_result,
                keep_last_releases
            FROM "organization_package"
            WHERE organization_id = :organization_id AND id = :id', [
            'organization_id' => $organizationId,
            'id' => $id,
        ]);
        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydratePackage($data));
    }

    /**
     * @param array<string,mixed> $data
     */
    private function hydratePackage(array $data): Package
    {
        $scanResult = isset($data['last_scan_status']) ?
            new ScanResult(
                new \DateTimeImmutable($data['last_scan_date']),
                $data['last_scan_status'],
                $data['latest_released_version'],
                $data['last_scan_result'],
            ) : null;

        return new Package(
            $data['id'],
            $data['type'],
            $data['repository_url'],
            $data['name'],
            $data['latest_released_version'],
            $data['latest_release_date'] !== null ? new \DateTimeImmutable($data['latest_release_date']) : null,
            $data['description'],
            $data['last_sync_at'] !== null ? new \DateTimeImmutable($data['last_sync_at']) : null,
            $data['last_sync_error'],
            $data['webhook_created_at'] !== null ? new \DateTimeImmutable($data['webhook_created_at']) : null,
            $scanResult,
            $data['keep_last_releases']
        );
    }
}
