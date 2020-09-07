<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\Api\Model;

use Buddy\Repman\Query\User\Model\ScanResult;

final class Package
{
    private string $id;
    private string $type;
    private string $url;
    private ?string $name;
    private ?string $latestReleasedVersion;
    private ?\DateTimeImmutable $latestReleaseDate;
    private ?string $description;
    private ?\DateTimeImmutable $lastSyncAt;
    private ?string $lastSyncError;
    private ?\DateTimeImmutable $webhookCreatedAt;
    private ?ScanResult $scanResult;

    public function __construct(
        string $id,
        string $type,
        string $url,
        ?string $name = null,
        ?string $latestReleasedVersion = null,
        ?\DateTimeImmutable $latestReleaseDate = null,
        ?string $description = null,
        ?\DateTimeImmutable $lastSyncAt = null,
        ?string $lastSyncError = null,
        ?\DateTimeImmutable $webhookCreatedAt = null,
        ?ScanResult $scanResult = null
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->url = $url;
        $this->name = $name;
        $this->latestReleasedVersion = $latestReleasedVersion;
        $this->latestReleaseDate = $latestReleaseDate;
        $this->description = $description;
        $this->lastSyncAt = $lastSyncAt;
        $this->lastSyncError = $lastSyncError;
        $this->webhookCreatedAt = $webhookCreatedAt;
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

    public function getLatestReleaseDate(): ?\DateTimeImmutable
    {
        return $this->latestReleaseDate;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function getLastSyncError(): ?string
    {
        return $this->lastSyncError;
    }

    public function getWebhookCreatedAt(): ?\DateTimeImmutable
    {
        return $this->webhookCreatedAt;
    }

    public function getIsSynchronizedSuccessfully(): bool
    {
        return $this->getName() !== null && $this->getLastSyncError() === null;
    }

    public function getScanResultStatus(): string
    {
        return $this->scanResult !== null ? $this->scanResult->status() : ScanResult::statusPending();
    }

    public function getScanResultDate(): ?\DateTimeImmutable
    {
        return $this->scanResult !== null ? $this->scanResult->date() : null;
    }

    /**
     * @return string[]
     */
    public function getLastScanResultContent(): array
    {
        return $this->scanResult !== null ? $this->scanResult->content() : [];
    }
}
