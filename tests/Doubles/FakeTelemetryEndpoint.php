<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Service\Telemetry\Endpoint;
use Buddy\Repman\Service\Telemetry\Entry;
use Buddy\Repman\Service\Telemetry\TechnicalEmail;

final class FakeTelemetryEndpoint implements Endpoint
{
    private string $sent = '';
    private string $emailAdded = '';
    private string $emailRemoved = '';

    public function send(Entry $entry): void
    {
        json_encode($entry, JSON_THROW_ON_ERROR);
        $this->sent = $entry->instance()->id();
    }

    public function addTechnicalEmail(TechnicalEmail $email): void
    {
        json_encode($email, JSON_THROW_ON_ERROR);
        $this->emailAdded = $email->instanceId();
    }

    public function removeTechnicalEmail(TechnicalEmail $email): void
    {
        json_encode($email, JSON_THROW_ON_ERROR);
        $this->emailRemoved = $email->instanceId();
    }

    public function wasEntrySent(string $instanceId): bool
    {
        return $this->sent === $instanceId;
    }

    public function entryWasNotSent(): bool
    {
        return $this->sent === '';
    }

    public function wasEmailRemoved(string $instanceId): bool
    {
        return $this->emailRemoved === $instanceId;
    }

    public function emailWasNotRemoved(): bool
    {
        return $this->emailRemoved === '';
    }

    public function wasEmailAdded(string $instanceId): bool
    {
        return $this->emailAdded === $instanceId;
    }
}
