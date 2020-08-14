<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\VersionQuery;

use Buddy\Repman\Entity\Organization\Package\Version as VersionEntity;
use Buddy\Repman\Query\Admin\VersionQuery;
use Buddy\Repman\Query\User\Model\Version;
use Doctrine\DBAL\Connection;

final class DbalVersionQuery implements VersionQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Version[]
     */
    public function findDevVersions(string $packageId): array
    {
        return array_map(function (array $data): Version {
            return new Version(
                $data['id'],
                $data['version'],
                $data['reference'],
                0,
                new \DateTimeImmutable()
            );
        }, $this->connection->fetchAll(
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
                ':stability' => VersionEntity::STABILITY_STABLE,
            ]
        ));
    }
}
