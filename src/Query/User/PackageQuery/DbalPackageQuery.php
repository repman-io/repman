<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\PackageQuery;

use Buddy\Repman\Query\User\Model\Installs;
use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Query\User\Model\WebhookRequest;
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
            'SELECT id, type, repository_url, name, latest_released_version, latest_release_date, description, last_sync_at, last_sync_error, webhook_created_at
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
            'SELECT id, type, repository_url, name, latest_released_version, latest_release_date, description, last_sync_at, last_sync_error, webhook_created_at
            FROM "organization_package"
            WHERE id = :id', [
            ':id' => $id,
        ]);
        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydratePackage($data));
    }

    public function getInstalls(string $packageId, int $lastDays = 30): Installs
    {
        return new Installs(
            array_map(function (array $row): Installs\Day {
                return new Installs\Day($row['date'], $row['count']);
            }, $this->connection->fetchAll('SELECT * FROM (SELECT COUNT(package_id), date FROM organization_package_download WHERE date > :date AND package_id = :package GROUP BY date) AS installs ORDER BY date ASC', [
                ':date' => (new \DateTimeImmutable())->modify(sprintf('-%s days', $lastDays))->format('Y-m-d'),
                ':package' => $packageId,
            ])),
            $lastDays,
            (int) $this->connection->fetchColumn('SELECT COUNT(package_id) FROM organization_package_download WHERE package_id = :package', [':package' => $packageId])
        );
    }

    public function findRecentWebhookRequests(string $packageId): array
    {
        return array_map(function (array $row): WebhookRequest {
            return new WebhookRequest($row['date'], $row['ip'], $row['user_agent']);
        }, $this->connection->fetchAll('SELECT date, ip, user_agent FROM organization_package_webhook_request WHERE package_id = :package ORDER BY date DESC LIMIT 10', [':package' => $packageId]));
    }

    /**
     * @param array<mixed> $data
     */
    private function hydratePackage(array $data): Package
    {
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
        );
    }
}
