<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry;

interface Endpoint
{
    public function send(string $userAgent, Entry $entry): void;
}
