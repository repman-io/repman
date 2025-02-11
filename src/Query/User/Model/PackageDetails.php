<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

use DateTimeImmutable;

final class PackageDetails
{
    use PackageScanResultTrait;

    public function __construct(
        private string $id,
        private string $organizationId,
        private string $url,
        private ?string $name = null,
        private ?string $latestReleasedVersion = null,
        private ?DateTimeImmutable $latestReleaseDate = null,
        private ?string $description = null,
        private ?string $lastSyncError = null,
        ?ScanResult $scanResult = null,
        private int $keepLastReleases = 0,
        private ?string $readme = null,
        private ?string $replacementPackage = null,
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

    public function url(): string
    {
        return $this->url;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function latestReleasedVersion(): ?string
    {
        return $this->latestReleasedVersion;
    }

    public function latestReleaseDate(): ?DateTimeImmutable
    {
        return $this->latestReleaseDate;
    }

    public function getKeepLastReleases(): int
    {
        return $this->keepLastReleases;
    }

    public function getReadme(): ?string
    {
        return $this->readme;
    }

    public function lastSyncError(): ?string
    {
        return $this->lastSyncError;
    }

    public function isSynchronizedSuccessfully(): bool
    {
        return $this->name !== null && $this->lastSyncError === null;
    }

    public function isAbandoned(): bool
    {
        return !is_null($this->replacementPackage);
    }

    public function getReplacementPackage(): ?string
    {
        return $this->replacementPackage;
    }

    public function isEnabledSecurityScan(): bool
    {
        return $this->enableSecurityScan;
    }
}
