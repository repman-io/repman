<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Telemetry\Endpoint;
use Buddy\Repman\Service\Telemetry\Entry;

final class FakeTelemetryEndpoint implements Endpoint
{
    public function send(Entry $entry): void
    {
        $entry->toString();
    }
}
