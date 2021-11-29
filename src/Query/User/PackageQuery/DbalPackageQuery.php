<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\PackageQuery;

use Buddy\Repman\Entity\Organization\Package\Version as VersionEntity;
use Buddy\Repman\Query\Filter as BaseFilter;
use Buddy\Repman\Query\User\Model\Installs;
use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\Model\Package\Link;
use Buddy\Repman\Query\User\Model\PackageDetails;
use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Query\User\Model\ScanResult;
use Buddy\Repman\Query\User\Model\Version;
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
    public function findAll(string $organizationId, Filter $filter): array
    {
        $filterSQL = $joinSQL = '';
        $params = [
            'organization_id' => $organizationId,
            'limit' => $filter->getLimit(),
            'offset' => $filter->getOffset(),
        ];

        if ($filter->hasLinkSearch()) {
            $filterSQL = ' AND l.target = :link';
            $params['link'] = $filter->getLinkSearch();
            $joinSQL = 'JOIN organization_package_link l ON (p.id = l.package_id AND p.organization_id = :organization_id) ';
        } elseif ($filter->hasSearchTerm()) {
            $filterSQL = ' AND (p.name ILIKE :term OR p.description ILIKE :term)';
            $params['term'] = '%'.$filter->getSearchTerm().'%';
        }

        $sortSQL = 'name ASC';

        $sortColumnMappings = [
            'name' => 'p.name',
            'version' => 'p.latest_released_version',
            'date' => 'p.latest_release_date',
        ];

        if ($filter->hasSort() && isset($sortColumnMappings[$filter->getSortColumn()])) {
            $sortSQL = $sortColumnMappings[$filter->getSortColumn()].' '.$filter->getSortOrder();
        }

        return array_map(
            function (array $data): Package {
                return $this->hydratePackage($data);
            },
            $this->connection->fetchAllAssociative(
                'SELECT
                p.id,
                p.organization_id,
                p.type,
                p.repository_url,
                p.name,
                p.latest_released_version,
                p.latest_release_date,
                p.description,
                p.last_sync_at,
                p.last_sync_error,
                p.webhook_created_at,
                p.webhook_created_error,
                p.last_scan_date,
                p.last_scan_status,
                p.last_scan_result,
                p.keep_last_releases
            FROM organization_package p '.$joinSQL
            .'WHERE p.organization_id = :organization_id
            '.$filterSQL.'
            GROUP BY p.id
            ORDER BY '.$sortSQL.'
            LIMIT :limit OFFSET :offset',
                $params
            )
        );
    }

    /**
     * @return PackageName[]
     */
    public function getAllNames(string $organizationId): array
    {
        return array_map(function (array $data): PackageName {
            return new PackageName($data['id'], $data['name']);
        }, $this->connection->fetchAllAssociative(
            'SELECT id, name
            FROM "organization_package"
            WHERE organization_id = :organization_id AND name IS NOT NULL',
            [
            'organization_id' => $organizationId,
        ]));
    }

    public function count(string $organizationId, Filter $filter): int
    {
        $filterSQL = $joinSQL = '';
        $params = [
            'organization_id' => $organizationId,
        ];

        if ($filter->hasLinkSearch()) {
            $filterSQL = ' AND l.target = :link';
            $params['link'] = $filter->getLinkSearch();
            $joinSQL = 'JOIN organization_package_link l ON (p.id = l.package_id AND p.organization_id = :organization_id) ';
        } elseif ($filter->hasSearchTerm()) {
            $filterSQL = ' AND (p.name ILIKE :term OR p.description ILIKE :term)';
            $params['term'] = '%'.$filter->getSearchTerm().'%';
        }

        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(DISTINCT p.id) FROM organization_package p '.$joinSQL
                .'WHERE p.organization_id = :organization_id'.$filterSQL,
                $params
            );
    }

    /**
     * @return Option<Package>
     */
    public function getById(string $id): Option
    {
        $data = $this->connection->fetchAssociative(
            'SELECT
                id,
                organization_id,
                type,
                repository_url,
                name,
                latest_released_version,
                latest_release_date,
                description,
                last_sync_at,
                last_sync_error,
                webhook_created_at,
                webhook_created_error,
                last_scan_date,
                last_scan_status,
                last_scan_result,
                keep_last_releases
            FROM "organization_package"
            WHERE id = :id', [
            'id' => $id,
        ]);
        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydratePackage($data));
    }

    /**
     * @return Option<PackageDetails>
     */
    public function getDetailsById(string $id): Option
    {
        $data = $this->connection->fetchAssociative(
            'SELECT
                id,
                organization_id,
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
                keep_last_releases,
                readme
            FROM "organization_package"
            WHERE id = :id', [
            'id' => $id,
        ]);
        if ($data === false) {
            return Option::none();
        }

        return Option::some($this->hydratePackageDetails($data));
    }

    public function versionCount(string $packageId): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(id) FROM "organization_package_version"
                WHERE package_id = :package_id',
                [
                    'package_id' => $packageId,
                ]
            );
    }

    public function getDependantCount(string $packageName, string $organizationId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT (DISTINCT l.package_id)
            FROM organization_package_link l
            JOIN organization_package p ON p.id = l.package_id
            WHERE l.target = :package_name
            AND p.organization_id = :organization_id', [
            'package_name' => $packageName,
            'organization_id' => $organizationId,
        ]);
    }

    /**
     * @return array<string,Link[]>
     */
    public function getLinks(string $packageId, string $organizationId): array
    {
        $links = [];
        foreach ($this->connection->fetchAllAssociative(
            'SELECT
                l.type,
                l.target,
                l.constraint,
                p.id as target_package_id
            FROM organization_package_link l
            LEFT JOIN organization_package p ON (p.name = l.target AND p.organization_id = :organization_id)
            WHERE package_id = :package_id', [
            'organization_id' => $organizationId,
            'package_id' => $packageId,
        ]) as $data) {
            $links[(string) $data['type']][] = new Link(
                $data['type'],
                $data['target'],
                $data['constraint'],
                $data['target_package_id']
            );
        }

        return $links;
    }

    /**
     * @return Version[]
     */
    public function getVersions(string $packageId, BaseFilter $filter): array
    {
        return array_map(function (array $data): Version {
            return new Version(
                $data['version'],
                $data['reference'],
                $data['size'],
                new \DateTimeImmutable($data['date'])
            );
        }, $this->connection->fetchAllAssociative(
            'SELECT
                id,
                version,
                reference,
                size,
                date
            FROM organization_package_version
            WHERE package_id = :package_id
            ORDER BY date DESC
            LIMIT :limit OFFSET :offset', [
            'package_id' => $packageId,
            'limit' => $filter->getLimit(),
            'offset' => $filter->getOffset(),
        ]));
    }

    public function getInstalls(string $packageId, int $lastDays = 30, ?string $version = null): Installs
    {
        $params = [
            'date' => (new \DateTimeImmutable())->modify(sprintf('-%s days', $lastDays))->format('Y-m-d'),
            'package' => $packageId,
        ];
        $query = 'SELECT * FROM (SELECT COUNT(package_id), date FROM organization_package_download WHERE date > :date AND package_id = :package ';

        if ($version !== null) {
            $query .= ' AND version = :version';
            $params['version'] = $version;
        }

        $query .= ' GROUP BY date) AS installs ORDER BY date ASC';

        return new Installs(
            array_map(function (array $row): Installs\Day {
                return new Installs\Day($row['date'], $row['count']);
            }, $this->connection->fetchAllAssociative($query, $params)),
            $lastDays,
            (int) $this->connection->fetchOne('SELECT COUNT(package_id) FROM organization_package_download WHERE package_id = :package', ['package' => $packageId])
        );
    }

    /**
     * @return string[]
     */
    public function getInstallVersions(string $packageId): array
    {
        return array_column($this->connection->fetchAllAssociative('
            SELECT DISTINCT version FROM organization_package_download
            WHERE package_id= :package ORDER BY version DESC', [
            'package' => $packageId,
        ]), 'version');
    }

    public function findRecentWebhookRequests(string $packageId): array
    {
        return array_map(function (array $row): WebhookRequest {
            return new WebhookRequest($row['date'], $row['ip'], $row['user_agent']);
        }, $this->connection->fetchAllAssociative('SELECT date, ip, user_agent FROM organization_package_webhook_request WHERE package_id = :package ORDER BY date DESC LIMIT 10', ['package' => $packageId]));
    }

    /**
     * @return ScanResult[]
     */
    public function getScanResults(string $packageId, BaseFilter $filter): array
    {
        return array_map(function (array $data): ScanResult {
            return new ScanResult(
                new \DateTimeImmutable($data['date']),
                $data['status'],
                $data['version'],
                $data['content'],
            );
        }, $this->connection->fetchAllAssociative(
            'SELECT
                date,
                status,
                version,
                content
            FROM organization_package_scan_result
            WHERE package_id = :package_id
            ORDER BY date DESC
            LIMIT :limit OFFSET :offset', [
                'package_id' => $packageId,
                'limit' => $filter->getLimit(),
                'offset' => $filter->getOffset(),
            ]));
    }

    public function getScanResultsCount(string $packageId): int
    {
        return (int) $this
            ->connection
            ->fetchOne(
                'SELECT COUNT(id) FROM "organization_package_scan_result"
                WHERE package_id = :package_id',
                [
                    'package_id' => $packageId,
                ]
            );
    }

    /**
     * @return PackageName[]
     */
    public function getAllSynchronized(int $limit = 20, int $offset = 0): array
    {
        return array_map(function (array $data): PackageName {
            return new PackageName($data['id'], $data['name'], $data['alias']);
        }, $this->connection->fetchAllAssociative(
            'SELECT p.id, p.name, o.alias
            FROM organization_package p
            JOIN organization o ON o.id = p.organization_id
            WHERE p.name IS NOT NULL AND p.last_sync_error IS NULL
            GROUP BY p.id, o.alias
            ORDER BY p.last_sync_at ASC
            LIMIT :limit OFFSET :offset', [
                'limit' => $limit,
                'offset' => $offset,
            ]
        ));
    }

    public function getAllSynchronizedCount(): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(id) FROM organization_package
            WHERE name IS NOT NULL AND last_sync_error IS NULL',
        );
    }

    /**
     * @param array<mixed> $data
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
            $data['organization_id'],
            $data['type'],
            $data['repository_url'],
            $data['name'],
            $data['latest_released_version'],
            $data['latest_release_date'] !== null ? new \DateTimeImmutable($data['latest_release_date']) : null,
            $data['description'],
            $data['last_sync_at'] !== null ? new \DateTimeImmutable($data['last_sync_at']) : null,
            $data['last_sync_error'],
            $data['webhook_created_at'] !== null ? new \DateTimeImmutable($data['webhook_created_at']) : null,
            $data['webhook_created_error'],
            $scanResult,
            $data['keep_last_releases'] ?? 0
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function hydratePackageDetails(array $data): PackageDetails
    {
        $scanResult = isset($data['last_scan_status']) ?
            new ScanResult(
                new \DateTimeImmutable($data['last_scan_date']),
                $data['last_scan_status'],
                $data['latest_released_version'],
                $data['last_scan_result'],
            ) : null;

        return new PackageDetails(
            $data['id'],
            $data['organization_id'],
            $data['repository_url'],
            $data['name'],
            $data['latest_released_version'],
            $data['latest_release_date'] !== null ? new \DateTimeImmutable($data['latest_release_date']) : null,
            $data['description'],
            $data['last_sync_error'],
            $scanResult,
            $data['keep_last_releases'] ?? 0,
            $data['readme'] ?? null,
        );
    }

    /**
     * @return Version[]
     */
    public function findNonStableVersions(string $packageId): array
    {
        return array_map(function (array $data): Version {
            return new Version(
                $data['version'],
                $data['reference'],
                0,
                new \DateTimeImmutable()
            );
        }, $this->connection->fetchAllAssociative(
            'SELECT
                id,
                version,
                reference
            FROM organization_package_version
            WHERE stability != :stability
            AND package_id = :package_id',
            [
                'package_id' => $packageId,
                'stability' => VersionEntity::STABILITY_STABLE,
            ]
        ));
    }
}
