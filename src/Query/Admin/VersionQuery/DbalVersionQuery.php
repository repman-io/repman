<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\VersionQuery;

use Buddy\Repman\Query\Admin\VersionQuery;
use Doctrine\DBAL\Connection;

final class DbalVersionQuery implements VersionQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function oldDistsCount(int $daysOld): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(v.id)
                FROM organization_package_version v
                JOIN organization_package p ON p.id = v.package_id
                JOIN organization o ON o.id = p.organization_id
                WHERE p.last_sync_at::date <= :date
                AND v.package_id NOT IN (
                    SELECT d.package_id
                    FROM organization_package_download d
                    WHERE d.package_id = v.package_id AND d.date > :date
                )',
            [
                ':date' => (new \DateTimeImmutable())
                    ->modify(sprintf('-%s days', $daysOld))
                    ->format('Y-m-d'),
            ]
        );
    }

    /**
     * @return array<array<string,string>>
     */
    public function findOldDists(int $daysOld = 30, int $limit = 100, int $offset = 0): array
    {
        return $this->connection->fetchAll(
            'SELECT
                v.id,
                v.version,
                v.reference,
                o.alias organization,
                p.name package_name
            FROM organization_package_version v
            JOIN organization_package p ON p.id = v.package_id
            JOIN organization o ON o.id = p.organization_id
            WHERE p.last_sync_at::date <= :date
            AND v.package_id NOT IN (
                SELECT d.package_id
                FROM organization_package_download d
                WHERE d.package_id = v.package_id AND d.date > :date
            )
            GROUP BY v.id, v.version, v.reference, organization, package_name
            LIMIT :limit OFFSET :offset',
            [
                ':date' => (new \DateTimeImmutable())
                    ->modify(sprintf('-%s days', $daysOld))
                    ->format('Y-m-d'),
                ':limit' => $limit,
                ':offset' => $offset,
            ]
        );
    }
}
