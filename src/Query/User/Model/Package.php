<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use DateTimeImmutable;

final class Package
{
    use PackageScanResultTrait;

    public function __construct(
        private string $id,
        private string $organizationId,
        private string $type,
        private string $url,
        private ?string $name = null,
        private ?string $latestReleasedVersion = null,
        private ?DateTimeImmutable $latestReleaseDate = null,
        private ?string $description = null,
        private ?DateTimeImmutable $lastSyncAt = null,
        private ?string $lastSyncError = null,
        private ?DateTimeImmutable $webhookCreatedAt = null,
        private ?string $webhookCreatedError = null,
        ?ScanResult $scanResult = null,
        private int $keepLastReleases = 0,
        private bool $enableSecurityScan = true,
    ) {
        $this->scanResult = $scanResult ?? null;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function latestReleasedVersion(): ?string
    {
        return $this->latestReleasedVersion;
    }

    public function latestReleaseDate(): ?DateTimeImmutable
    {
        return $this->latestReleaseDate;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function lastSyncAt(): ?DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function lastSyncError(): ?string
    {
        return $this->lastSyncError;
    }

    public function webhookCreatedAt(): ?DateTimeImmutable
    {
        return $this->webhookCreatedAt;
    }

    public function webhookCreatedError(): ?string
    {
        return $this->webhookCreatedError;
    }

    public function allowToAutoAddWebhook(): bool
    {
        return in_array($this->type, ['github-oauth', 'gitlab-oauth', 'bitbucket-oauth'], true);
    }

    public function isSynchronizedSuccessfully(): bool
    {
        return $this->name !== null && $this->lastSyncError === null;
    }

    public function keepLastReleases(): int
    {
        return $this->keepLastReleases;
    }

    public function isEnabledSecurityScan(): bool
    {
        return $this->enableSecurityScan;
    }
}
