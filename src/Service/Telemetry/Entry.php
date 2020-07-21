<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry;

final class Entry
{
    private \DateTimeImmutable $date;
    private string $instanceId;
    private string $repmanVersion;
    private string $osVersion;
    private string $phpVersion;
    private int $allOrganizationsCount;
    private int $publicOrganizationsCount;
    private int $allPackagesCount;
    private int $allPackagesInstalls;
    private int $allTokensCount;
    private int $allUsersCount;

    public function __construct(
        \DateTimeImmutable $date,
        string $instanceId,
        string $repmanVersion,
        string $osVersion,
        string $phpVersion,
        int $allOrganizationsCount,
        int $publicOrganizationsCount,
        int $allPackagesCount,
        int $allPackagesInstalls,
        int $allTokensCount,
        int $allUsersCount
    ) {
        $this->date = $date;
        $this->instanceId = $instanceId;
        $this->repmanVersion = $repmanVersion;
        $this->osVersion = $osVersion;
        $this->phpVersion = $phpVersion;
        $this->allOrganizationsCount = $allOrganizationsCount;
        $this->publicOrganizationsCount = $publicOrganizationsCount;
        $this->allPackagesCount = $allPackagesCount;
        $this->allPackagesInstalls = $allPackagesInstalls;
        $this->allTokensCount = $allTokensCount;
        $this->allUsersCount = $allUsersCount;
    }

    public function toString(): string
    {
        return (string) \json_encode([
            'id' => \sprintf('%s_%s', $this->date->format('Ymd'), $this->instanceId),
            'date' => $this->date->format('Y-m-d\T00:00:00.000\Z'),
            'instance_id' => $this->instanceId,
            'repman_version' => $this->repmanVersion,
            'os_version' => $this->osVersion,
            'php_version' => $this->phpVersion,
            'all_organizations_count' => $this->allOrganizationsCount,
            'public_organizations_count' => $this->publicOrganizationsCount,
            'all_packages_count' => $this->allPackagesCount,
            'all_packages_installs' => $this->allPackagesInstalls,
            'all_tokens_count' => $this->allTokensCount,
            'all_users_count' => $this->allUsersCount,
        ]);
    }
}
