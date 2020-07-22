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
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;

final class Telemetry
{
    private string $instanceIdFile;
    private TelemetryQuery $query;
    private Endpoint $endpoint;
    private Config $config;
    private ListableReceiverInterface $failedTransport;
    private ProxyRegister $proxies;

    public function __construct(string $instanceIdFile, TelemetryQuery $query, Endpoint $endpoint, Config $config, ListableReceiverInterface $failedTransport, ProxyRegister $proxies)
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
        $failedMessages = 0;
        foreach ($this->failedTransport->all() as $_) {
            ++$failedMessages;
        }

        $proxyPackages = 0;
        $this->proxies
            ->all()
            ->forEach(function (PackageProxy $proxy) use (&$proxyPackages): void {
                $proxyPackages += $proxy->syncedPackages()->length();
            });

        $this->endpoint->send(
            new Entry(
                $date,
                new Instance(
                    $this->instanceId(),
                    Kernel::REPMAN_VERSION,
                    sprintf('%s %s', php_uname('s'), php_uname('r')),
                    PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION,
                    $this->query->usersCount(),
                    $failedMessages,
                    $this->config->getAll(),
                ),
                $this->getOrganizations(),
                new Downloads(
                    $this->query->publicDownloads(),
                    $this->query->privateDownloads()
                ),
                new Proxy($proxyPackages)
            )
        );
    }

    /**
     * @return Organization[]
     */
    private function getOrganizations(): array
    {
        $count = $this->query->organizationsCount();
        $limit = 100;

        $organizations = [];
        for ($offset = 0; $offset < $count; $offset += $limit) {
            foreach ($this->query->organizations($limit, $offset) as $organization) {
                $this->getPackages($organization);

                $organizations[] = $organization;
            }
        }

        return $organizations;
    }

    private function getPackages(Organization $organization): void
    {
        $count = $this->query->packagesCount($organization->id());
        $limit = 100;

        for ($offset = 0; $offset < $count; $offset += $limit) {
            $organization->addPackages(
                $this->query->packages($organization->id(), $limit, $offset)
            );
        }
    }
}
