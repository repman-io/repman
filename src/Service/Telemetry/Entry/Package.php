<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

use DateTime;
use DateTimeImmutable;
use JsonSerializable;

final class Package implements JsonSerializable
{
    public function __construct(private readonly string $type, private readonly ?DateTimeImmutable $lastRelease, private readonly ?DateTimeImmutable $lastSync, private readonly ?DateTimeImmutable $lastScan, private readonly bool $hasError, private readonly bool $hasWebhook, private readonly string $scanStatus, private readonly int $downloads, private readonly int $webhookRequests)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'lastRelease' => $this->lastRelease instanceof DateTimeImmutable ? $this->lastRelease->format(DateTime::ATOM) : null,
            'lastSync' => $this->lastSync instanceof DateTimeImmutable ? $this->lastSync->format(DateTime::ATOM) : null,
            'lastScan' => $this->lastScan instanceof DateTimeImmutable ? $this->lastScan->format(DateTime::ATOM) : null,
            'hasError' => $this->hasError,
            'hasWebhook' => $this->hasWebhook,
            'scanStatus' => $this->scanStatus,
            'downloads' => $this->downloads,
            'webhookRequests' => $this->webhookRequests,
        ];
    }
}
