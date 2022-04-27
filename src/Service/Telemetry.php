<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

use Buddy\Repman\Kernel;
use Buddy\Repman\Query\Admin\TelemetryQuery;
use Buddy\Repman\Service\Proxy as PackageProxy;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Buddy\Repman\Service\Telemetry\Endpoint;
use Buddy\Repman\Service\Telemetry\Entry;
use Buddy\Repman\Service\Telemetry\Entry\Downloads;
use Buddy\Repman\Service\Telemetry\Entry\Instance;
use Buddy\Repman\Service\Telemetry\Entry\Organization;
use Buddy\Repman\Service\Telemetry\Entry\Proxy;
use Buddy\Repman\Service\Telemetry\TechnicalEmail;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;

final class Telemetry
{
    private string $instanceIdFile;
    private TelemetryQuery $query;
    private Endpoint $endpoint;
    private Config $config;
    private MessageCountAwareInterface $failedTransport;
    private ProxyRegister $proxies;

    public function __construct(string $instanceIdFile, TelemetryQuery $query, Endpoint $endpoint, Config $config, MessageCountAwareInterface $failedTransport, ProxyRegister $proxies)
    {
        $this->instanceIdFile = $instanceIdFile;
        $this->query = $query;
        $this->endpoint = $endpoint;
        $this->config = $config;
        $this->failedTransport = $failedTransport;
        $this->proxies = $proxies;
    }

    public function docsUrl(): string
    {
        return 'https://repman.io/docs/standalone#telemetry';
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
                new Instance(
                    $this->instanceId(),
                    Kernel::REPMAN_VERSION,
                    sprintf('%s %s', php_uname('s'), php_uname('r')),
                    PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION,
                    $this->query->usersCount(),
                    $this->failedTransport->getMessageCount(),
                    $this->getConfig(),
                ),
                $this->getOrganizations($date),
                new Downloads(
                    $this->query->proxyDownloads($date),
                    $this->query->privateDownloads($date)
                ),
                new Proxy((int) $this->proxies->all()->map(fn (PackageProxy $p) => $p->syncedPackages()->length())->sum())
            )
        );
    }

    public function addTechnicalEmail(string $technicalEmail): void
    {
        if (!$this->isInstanceIdPresent()) {
            $this->generateInstanceId();
        }

        $this->endpoint->addTechnicalEmail(new TechnicalEmail($technicalEmail, $this->instanceId()));
    }

    public function removeTechnicalEmail(string $technicalEmail): void
    {
        if (!$this->isInstanceIdPresent()) {
            return;
        }

        $this->endpoint->removeTechnicalEmail(new TechnicalEmail($technicalEmail, $this->instanceId()));
    }

    /**
     * @return Organization[]
     */
    private function getOrganizations(\DateTimeImmutable $date): array
    {
        $count = $this->query->organizationsCount();
        $limit = 100;

        $organizations = [];
        for ($offset = 0; $offset < $count; $offset += $limit) {
            foreach ($this->query->organizations($limit, $offset) as $organization) {
                $this->getPackages($organization, $date);

                $organizations[] = $organization;
            }
        }

        return $organizations;
    }

    private function getPackages(Organization $organization, \DateTimeImmutable $date): void
    {
        $count = $this->query->packagesCount($organization->id());
        $limit = 100;

        for ($offset = 0; $offset < $count; $offset += $limit) {
            $organization->addPackages(
                $this->query->packages($organization->id(), $date, $limit, $offset)
            );
        }
    }

    /**
     * @return array<string,string>
     */
    private function getConfig(): array
    {
        return array_filter(
            $this->config->getAll(),
            fn ($k) => $k !== Config::TECHNICAL_EMAIL, ARRAY_FILTER_USE_KEY
        );
    }
}
