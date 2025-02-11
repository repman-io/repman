<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry;

use Buddy\Repman\Service\Telemetry\Entry\Downloads;
use Buddy\Repman\Service\Telemetry\Entry\Instance;
use Buddy\Repman\Service\Telemetry\Entry\Organization;
use Buddy\Repman\Service\Telemetry\Entry\Proxy;
use DateTimeImmutable;
use JsonSerializable;
use function sprintf;

final class Entry implements JsonSerializable
{
    /**
     * @param Organization[] $organizations
     */
    public function __construct(private readonly DateTimeImmutable $date, private readonly Instance $instance, private readonly array $organizations, private readonly Downloads $downloads, private readonly Proxy $proxy)
    {
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
            'id' => sprintf('%s_%s', $this->date->format('Ymd'), $this->instance->id()),
            'date' => $this->date->format('Y-m-d'),
            'instance' => $this->instance,
            'organizations' => $this->organizations,
            'downloads' => $this->downloads,
            'proxy' => $this->proxy,
        ];
    }
}
