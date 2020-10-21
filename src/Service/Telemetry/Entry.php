<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry;

use Buddy\Repman\Service\Telemetry\Entry\Downloads;
use Buddy\Repman\Service\Telemetry\Entry\Instance;
use Buddy\Repman\Service\Telemetry\Entry\Organization;
use Buddy\Repman\Service\Telemetry\Entry\Proxy;

final class Entry implements \JsonSerializable
{
    private \DateTimeImmutable $date;
    private Instance $instance;
    private Downloads $downloads;
    private Proxy $proxy;

    /**
     * @var Organization[]
     */
    private array $organizations;

    /**
     * @param Organization[] $organizations
     */
    public function __construct(
        \DateTimeImmutable $date,
        Instance $instance,
        array $organizations,
        Downloads $downloads,
        Proxy $proxy
    ) {
        $this->date = $date;
        $this->instance = $instance;
        $this->organizations = $organizations;
        $this->downloads = $downloads;
        $this->proxy = $proxy;
    }

    public function instance(): Instance
    {
        return $this->instance;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => \sprintf('%s_%s', $this->date->format('Ymd'), $this->instance->id()),
            'date' => $this->date->format('Y-m-d'),
            'instance' => $this->instance,
            'organizations' => $this->organizations,
            'downloads' => $this->downloads,
            'proxy' => $this->proxy,
        ];
    }
}
