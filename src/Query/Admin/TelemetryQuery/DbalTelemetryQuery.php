<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\TelemetryQuery;

use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Query\Admin\TelemetryQuery;
use Buddy\Repman\Service\Telemetry\Entry\Organization;
use Buddy\Repman\Service\Telemetry\Entry\Package;
use Doctrine\DBAL\Connection;

final class DbalTelemetryQuery implements TelemetryQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function usersCount(): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(id) FROM "user"',
            );
    }

    /**
     * @return Organization[]
     */
    public function organizations(int $limit = 100, int $offset = 0): array
    {
        return array_map(function (array $data): Organization {
            return new Organization(
                $data['id'],
                $data['tokens'],
                $data['has_anonymous_access'],
                $data['members'],
                $data['owners'],
            );
        }, $this->connection->fetchAll(
            'SELECT
                o.id,
                COUNT(t.value) tokens,
                o.has_anonymous_access,
                COUNT(m.id) members,
                COUNT(ow.id) owners
            FROM "organization" o
            LEFT JOIN "organization_token" t ON t.organization_id = o.id
            LEFT JOIN "organization_member" m ON m.organization_id = o.id AND m.role = :role_member
            LEFT JOIN "organization_member" ow ON ow.organization_id = o.id AND ow.role = :role_owner
            GROUP BY o.id
            LIMIT :limit OFFSET :offset',
            [
                ':role_member' => Member::ROLE_MEMBER,
                ':role_owner' => Member::ROLE_OWNER,
                ':limit' => $limit,
                ':offset' => $offset,
            ])
        );
    }

    public function organizationsCount(): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(id) FROM "organization"'
            );
    }

    /**
     * @return Package[]
     */
    public function packages(string $organizationId, int $limit = 100, int $offset = 0): array
    {
        return array_map(function (array $data): Package {
            return new Package(
                $data['type'],
                new \DateTimeImmutable($data['latest_release_date']),
                new \DateTimeImmutable($data['last_sync_at']),
                new \DateTimeImmutable($data['last_scan_date']),
                $data['last_sync_error'] !== null,
                $data['webhook_created_at'] !== null,
                $data['last_scan_status'],
                $data['downloads'],
                $data['webhooks'],
            );
        }, $this->connection->fetchAll(
            'SELECT
                p.type,
                p.latest_release_date,
                p.last_sync_at,
                p.last_scan_date,
                p.last_sync_error,
                p.webhook_created_at,
                p.last_scan_status,
                COUNT(d.id) downloads,
                COUNT(w.date) webhooks
            FROM "organization_package" p
            LEFT JOIN "organization_package_download" d ON d.package_id = p.id
            LEFT JOIN "organization_package_webhook_request" w ON w.package_id = p.id
            WHERE p.organization_id = :organization_id
            GROUP BY p.id
            LIMIT :limit OFFSET :offset',
            [
                ':organization_id' => $organizationId,
                ':limit' => $limit,
                ':offset' => $offset,
            ])
        );
    }

    public function packagesCount(string $organizationId): int
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

    public function publicDownloads(): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(pd.id) FROM "organization_package_download" pd
                JOIN "organization_package" p ON p.id = pd.package_id
                JOIN "organization" o ON o.id = p.organization_id
                WHERE o.has_anonymous_access = true
                GROUP BY pd.id',
            );
    }

    public function privateDownloads(): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(pd.id) FROM "organization_package_download" pd
                JOIN "organization_package" p ON p.id = pd.package_id
                JOIN "organization" o ON o.id = p.organization_id
                WHERE o.has_anonymous_access = false
                GROUP BY pd.id',
            );
    }
}
