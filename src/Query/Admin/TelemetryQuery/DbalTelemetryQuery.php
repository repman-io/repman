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
            ->fetchOne(
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
        }, $this->connection->fetchAllAssociative(
            'SELECT
                o.id,
                COUNT(t.value) tokens,
                o.has_anonymous_access,
                COUNT(m.id) FILTER (WHERE m.role = :role_member) members,
                COUNT(m.id) FILTER (WHERE m.role = :role_owner) owners
            FROM "organization" o
            LEFT JOIN "organization_token" t ON t.organization_id = o.id
            LEFT JOIN "organization_member" m ON m.organization_id = o.id
            GROUP BY o.id
            LIMIT :limit OFFSET :offset',
            [
                'role_member' => Member::ROLE_MEMBER,
                'role_owner' => Member::ROLE_OWNER,
                'limit' => $limit,
                'offset' => $offset,
            ])
        );
    }

    public function organizationsCount(): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(id) FROM "organization"'
            );
    }

    /**
     * @return Package[]
     */
    public function packages(string $organizationId, \DateTimeImmutable $till, int $limit = 100, int $offset = 0): array
    {
        return array_map(function (array $data): Package {
            return new Package(
                $data['type'],
                $data['latest_release_date'] === null ? null : new \DateTimeImmutable($data['latest_release_date']),
                $data['last_sync_at'] === null ? null : new \DateTimeImmutable($data['last_sync_at']),
                $data['last_scan_date'] === null ? null : new \DateTimeImmutable($data['last_scan_date']),
                $data['last_sync_error'] !== null,
                $data['webhook_created_at'] !== null,
                $data['last_scan_status'] ?? '',
                $data['downloads'],
                $data['webhooks'],
            );
        }, $this->connection->fetchAllAssociative(
            'SELECT
                p.type,
                p.latest_release_date,
                p.last_sync_at,
                p.last_scan_date,
                p.last_sync_error,
                p.webhook_created_at,
                p.last_scan_status,
                (SELECT COUNT(d.id) FROM "organization_package_download" d WHERE d.package_id = p.id AND d.date::date <= :till) downloads,
                (SELECT COUNT(w.date) FROM "organization_package_webhook_request" w WHERE w.package_id = p.id AND w.date::date <= :till) webhooks
            FROM "organization_package" p
            WHERE p.organization_id = :organization_id
            LIMIT :limit OFFSET :offset',
            [
                'organization_id' => $organizationId,
                'till' => $till->format('Y-m-d'),
                'limit' => $limit,
                'offset' => $offset,
            ])
        );
    }

    public function packagesCount(string $organizationId): int
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

    public function proxyDownloads(\DateTimeImmutable $till): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(date) FROM "proxy_package_download"
                WHERE date::date <= :till',
                ['till' => $till->format('Y-m-d')]
            );
    }

    public function privateDownloads(\DateTimeImmutable $till): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(date) FROM "organization_package_download"
                WHERE date::date <= :till',
                ['till' => $till->format('Y-m-d')]
            );
    }
}
