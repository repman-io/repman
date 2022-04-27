<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Telemetry\Entry;

final class Package implements \JsonSerializable
{
    private string $type;
    private ?\DateTimeImmutable $lastRelease;
    private ?\DateTimeImmutable $lastSync;
    private ?\DateTimeImmutable $lastScan;
    private bool $hasError;
    private bool $hasWebhook;
    private string $scanStatus;
    private int $downloads;
    private int $webhookRequests;

    public function __construct(
        string $type,
        ?\DateTimeImmutable $lastRelease,
        ?\DateTimeImmutable $lastSync,
        ?\DateTimeImmutable $lastScan,
        bool $hasError,
        bool $hasWebhook,
        string $scanStatus,
        int $downloads,
        int $webhookRequests
    ) {
        $this->type = $type;
        $this->lastRelease = $lastRelease;
        $this->lastSync = $lastSync;
        $this->lastScan = $lastScan;
        $this->hasError = $hasError;
        $this->hasWebhook = $hasWebhook;
        $this->scanStatus = $scanStatus;
        $this->downloads = $downloads;
        $this->webhookRequests = $webhookRequests;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'lastRelease' => $this->lastRelease instanceof \DateTimeImmutable ? $this->lastRelease->format(\DateTime::ATOM) : null,
            'lastSync' => $this->lastSync instanceof \DateTimeImmutable ? $this->lastSync->format(\DateTime::ATOM) : null,
            'lastScan' => $this->lastScan instanceof \DateTimeImmutable ? $this->lastScan->format(\DateTime::ATOM) : null,
            'hasError' => $this->hasError,
            'hasWebhook' => $this->hasWebhook,
            'scanStatus' => $this->scanStatus,
            'downloads' => $this->downloads,
            'webhookRequests' => $this->webhookRequests,
        ];
    }
}
