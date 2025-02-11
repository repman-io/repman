<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

use Buddy\Repman\Query\User\Model\ScanResult;
use DateTime;
use DateTimeImmutable;
use JsonSerializable;

final class Package implements JsonSerializable
{
    private readonly ?ScanResult $scanResult;

    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly string $url,
        private readonly ?string $name = null,
        private readonly ?string $latestReleasedVersion = null,
        private readonly ?DateTimeImmutable $latestReleaseDate = null,
        private readonly ?string $description = null,
        private readonly ?DateTimeImmutable $lastSyncAt = null,
        private readonly ?string $lastSyncError = null,
        private readonly ?DateTimeImmutable $webhookCreatedAt = null,
        ?ScanResult $scanResult = null,
        private readonly int $keepLastReleases = 0,
        private readonly bool $enableSecurityScan = true,
    ) {
        $this->scanResult = $scanResult ?? null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getLatestReleasedVersion(): ?string
    {
        return $this->latestReleasedVersion;
    }

    public function getLatestReleaseDate(): ?DateTimeImmutable
    {
        return $this->latestReleaseDate;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLastSyncAt(): ?DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function getLastSyncError(): ?string
    {
        return $this->lastSyncError;
    }

    public function getWebhookCreatedAt(): ?DateTimeImmutable
    {
        return $this->webhookCreatedAt;
    }

    public function getIsSynchronizedSuccessfully(): bool
    {
        return $this->name !== null && $this->lastSyncError === null;
    }

    public function getScanResultStatus(): string
    {
        return $this->scanResult instanceof ScanResult ? $this->scanResult->status() : ScanResult::statusPending();
    }

    public function getScanResultDate(): ?DateTimeImmutable
    {
        return $this->scanResult instanceof ScanResult ? $this->scanResult->date() : null;
    }

    /**
     * @return string[]
     */
    public function getLastScanResultContent(): array
    {
        return $this->scanResult instanceof ScanResult ? $this->scanResult->content() : [];
    }

    public function getKeepLastReleases(): int
    {
        return $this->keepLastReleases;
    }

    public function isEnabledSecurityScan(): bool
    {
        return $this->enableSecurityScan;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'url' => $this->url,
            'name' => $this->name,
            'latestReleasedVersion' => $this->latestReleasedVersion,
            'latestReleaseDate' => $this->latestReleaseDate instanceof DateTimeImmutable ? $this->latestReleaseDate->format(DateTime::ATOM) : null,
            'description' => $this->description,
            'lastSyncAt' => $this->lastSyncAt instanceof DateTimeImmutable ? $this->lastSyncAt->format(DateTime::ATOM) : null,
            'lastSyncError' => $this->lastSyncError,
            'webhookCreatedAt' => $this->webhookCreatedAt instanceof DateTimeImmutable ? $this->webhookCreatedAt->format(DateTime::ATOM) : null,
            'isSynchronizedSuccessfully' => $this->getIsSynchronizedSuccessfully(),
            'scanResultDate' => $this->getScanResultDate() instanceof DateTimeImmutable ? $this->getScanResultDate()->format(DateTime::ATOM) : null,
            'scanResultStatus' => $this->getScanResultStatus(),
            'lastScanResultContent' => $this->getLastScanResultContent(),
            'keepLastReleases' => $this->keepLastReleases,
            'enableSecurityScan' => $this->enableSecurityScan,
        ];
    }
}
