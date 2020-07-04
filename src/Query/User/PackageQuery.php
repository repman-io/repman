<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User;

use Buddy\Repman\Query\User\Model\Installs;
use Buddy\Repman\Query\User\Model\Package;
use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Query\User\Model\ScanResult;
use Buddy\Repman\Query\User\Model\Version;
use Buddy\Repman\Query\User\Model\WebhookRequest;
use Munus\Control\Option;

interface PackageQuery
{
    /**
     * @return Package[]
     */
    public function findAll(string $organizationId, int $limit = 20, int $offset = 0): array;

    /**
     * @return PackageName[]
     */
    public function getAllNames(string $organizationId): array;

    public function count(string $organizationId): int;

    /**
     * @return Option<Package>
     */
    public function getById(string $id): Option;

    public function versionCount(string $packageId): int;

    /**
     * @return Version[]
     */
    public function getVersions(string $packageId, int $limit = 20, int $offset = 0): array;

    public function getInstalls(string $packageId, int $lastDays = 30, ?string $version = null): Installs;

    /**
     * @return string[]
     */
    public function getInstallVersions(string $packageId): array;

    /**
     * @return WebhookRequest[]
     */
    public function findRecentWebhookRequests(string $packageId): array;

    /**
     * @return ScanResult[]
     */
    public function getScanResults(string $packageId, int $limit = 20, int $offset = 0): array;

    public function getScanResultsCount(string $packageId): int;

    /**
     * @return PackageName[]
     */
    public function getAllSynchronized(int $limit = 20, int $offset = 0): array;

    public function getAllSynchronizedCount(): int;
}
