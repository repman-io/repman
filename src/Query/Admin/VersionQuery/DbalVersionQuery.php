<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\VersionQuery;

use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Query\Admin\VersionQuery;
use Doctrine\DBAL\Connection;

final class DbalVersionQuery implements VersionQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function oldDistsCount(): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(*) FROM (
                    SELECT COUNT(v.package_id)
                    FROM organization_package_version v
                    JOIN organization_package p ON p.id = v.package_id
                    WHERE v.stability != :stability
                    GROUP BY p.id
                    HAVING COUNT(v.id) > 1
                ) count',
            [
                ':stability' => Version::STABILITY_STABLE,
            ]
        );
    }

    /**
     * @return array<array<string,string>>
     */
    public function findPackagesWithDevVersions(int $limit = 100, int $offset = 0): array
    {
        return $this->connection->fetchAll(
            'SELECT
                p.id,
                p.name,
                o.alias organization
            FROM organization_package p
            JOIN organization_package_version v ON p.id = v.package_id AND v.stability != :stability
            JOIN organization o ON o.id = p.organization_id
            GROUP BY p.id, o.alias
            HAVING COUNT(v.id) > 1
            LIMIT :limit OFFSET :offset',
            [
                ':stability' => Version::STABILITY_STABLE,
                ':limit' => $limit,
                ':offset' => $offset,
            ]
        );
    }

    /**
     * @return array<array<string,string>>
     */
    public function findPackagesDevVersions(string $packageId): array
    {
        return $this->connection->fetchAll(
            'SELECT
                v.id,
                v.version,
                v.reference
            FROM organization_package_version v
            WHERE v.stability != :stability
            AND v.package_id = :package_id
            AND v.id != (
                SELECT vv.id
                FROM organization_package_version vv
                WHERE vv.package_id = v.package_id AND stability != :stability
                ORDER BY vv.date DESC
                LIMIT 1
            )',
            [
                ':package_id' => $packageId,
                ':stability' => Version::STABILITY_STABLE,
            ]
        );
    }
}
