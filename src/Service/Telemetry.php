<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Kernel;
use Buddy\Repman\Query\Admin\TelemetryQuery;
use Buddy\Repman\Service\Telemetry\Endpoint;
use Buddy\Repman\Service\Telemetry\Entry;
use Ramsey\Uuid\Uuid;

final class Telemetry
{
    private string $instanceIdFile;
    private TelemetryQuery $query;
    private Endpoint $endpoint;

    public function __construct(string $instanceIdFile, TelemetryQuery $query, Endpoint $endpoint)
    {
        $this->instanceIdFile = $instanceIdFile;
        $this->query = $query;
        $this->endpoint = $endpoint;
    }

    public function docsUrl(): string
    {
        return 'https://repman.io/docs/telemetry';
    }

    public function generateInstanceId(): void
    {
        if (!$this->isInstanceIdPresent()) {
            \file_put_contents($this->instanceIdFile, Uuid::uuid4());
        }
    }

    public function isInstanceIdPresent(): bool
    {
        return \file_exists($this->instanceIdFile);
    }

    public function instanceId(): string
    {
        return (string) \file_get_contents($this->instanceIdFile);
    }

    public function collectAndSend(\DateTimeImmutable $date): void
    {
        $this->endpoint->send(
            new Entry(
                $date,
                $this->instanceId(),
                Kernel::REPMAN_VERSION,
                $this->osVersion(),
                $this->phpVersion(),
                $this->query->allOrganizationsCount($date),
                $this->query->publicOrganizationsCount($date),
                $this->query->allPackagesCount($date),
                $this->query->allPackagesInstalls($date),
                $this->query->allTokensCount($date),
                $this->query->allUsersCount($date),
            )
        );
    }

    private function osVersion(): string
    {
        return sprintf('%s %s', php_uname('s'), php_uname('r'));
    }

    private function phpVersion(): string
    {
        return 'PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
    }
}
