<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin;

use Buddy\Repman\Service\Telemetry\Entry\Organization;
use Buddy\Repman\Service\Telemetry\Entry\Package;

interface TelemetryQuery
{
    /**
     * @return Organization[]
     */
    public function organizations(int $limit = 100, int $offset = 0): array;

    public function organizationsCount(): int;

    /**
     * @return Package[]
     */
    public function packages(string $organizationId, \DateTimeImmutable $till, int $limit = 100, int $offset = 0): array;

    public function packagesCount(string $organizationId): int;

    public function usersCount(): int;

    public function privateDownloads(\DateTimeImmutable $till): int;

    public function proxyDownloads(\DateTimeImmutable $till): int;
}
