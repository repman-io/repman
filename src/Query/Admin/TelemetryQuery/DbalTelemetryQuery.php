<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Admin\TelemetryQuery;

use Buddy\Repman\Query\Admin\TelemetryQuery;
use Doctrine\DBAL\Connection;

final class DbalTelemetryQuery implements TelemetryQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function allOrganizationsCount(\DateTimeImmutable $date): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(id) FROM "organization" WHERE created_at::date <= :date',
                $this->params($date)
            );
    }

    public function publicOrganizationsCount(\DateTimeImmutable $date): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(id) FROM "organization" WHERE has_anonymous_access = true AND created_at::date <= :date',
                $this->params($date)
            );
    }

    public function allPackagesCount(\DateTimeImmutable $date): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(id) FROM "organization_package" WHERE last_sync_at::date <= :date',
                $this->params($date)
            );
    }

    public function allPackagesInstalls(\DateTimeImmutable $date): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(id) FROM "organization_package_download" WHERE date <= :date',
                $this->params($date)
            );
    }

    public function allTokensCount(\DateTimeImmutable $date): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(value) FROM "organization_token" WHERE created_at::date <= :date',
                $this->params($date)
            );
    }

    public function allUsersCount(\DateTimeImmutable $date): int
    {
        return (int) $this
            ->connection
            ->fetchColumn(
                'SELECT COUNT(id) FROM "user" WHERE created_at::date <= :date',
                $this->params($date)
            );
    }

    /**
     * @return array<string,string>
     */
    private function params(\DateTimeImmutable $date): array
    {
        return [
            ':date' => $date->format('Y-m-d'),
        ];
    }
}
