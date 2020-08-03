<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Telemetry\Endpoint;
use Buddy\Repman\Service\Telemetry\Entry;
use Buddy\Repman\Service\Telemetry\TechnicalEmail;

final class FakeTelemetryEndpoint implements Endpoint
{
    private bool $sent = false;
    private bool $emailAdded = false;
    private bool $emailRemoved = false;

    public function send(Entry $entry): void
    {
        json_encode($entry);

        $this->sent = true;
    }

    public function addTechnicalEmail(TechnicalEmail $email): void
    {
        json_encode($email);

        $this->emailAdded = true;
    }

    public function removeTechnicalEmail(TechnicalEmail $email): void
    {
        json_encode($email);

        $this->emailRemoved = true;
    }

    public function sent(): bool
    {
        return $this->sent;
    }

    public function emailRemoved(): bool
    {
        return $this->emailRemoved;
    }

    public function emailAdded(): bool
    {
        return $this->emailAdded;
    }
}
