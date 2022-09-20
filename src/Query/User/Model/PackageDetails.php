<?php

declare(strict_types=1);

namespace Buddy\Repman\Query\User\Model;

final class PackageDetails
{
    use PackageScanResultTrait;

    private string $id;
    private string $organizationId;
    private string $url;
    private ?string $name;
    private ?string $description;
    private ?string $latestReleasedVersion;
    private ?\DateTimeImmutable $latestReleaseDate;
    private ?string $lastSyncError;
    private int $keepLastReleases;
    private ?string $readme;
    private ?string $replacementPackage;
    private bool $enableSecurityScan;

    public function __construct(
        string $id,
        string $organizationId,
        string $url,
        ?string $name = null,
        ?string $latestReleasedVersion = null,
        ?\DateTimeImmutable $latestReleaseDate = null,
        ?string $description = null,
        ?string $lastSyncError = null,
        ?ScanResult $scanResult = null,
        int $keepLastReleases = 0,
        ?string $readme = null,
        ?string $replacementPackage = null,
        bool $enableSecurityScan = true
    ) {
        $this->id = $id;
        $this->organizationId = $organizationId;
        $this->url = $url;
        $this->name = $name;
        $this->latestReleasedVersion = $latestReleasedVersion;
        $this->latestReleaseDate = $latestReleaseDate;
        $this->description = $description;
        $this->lastSyncError = $lastSyncError;
        $this->scanResult = $scanResult ?? null;
        $this->keepLastReleases = $keepLastReleases;
        $this->readme = $readme;
        $this->replacementPackage = $replacementPackage;
        $this->enableSecurityScan = $enableSecurityScan;
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

    public function latestReleaseDate(): ?\DateTimeImmutable
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
        return $this->name() !== null && $this->lastSyncError() === null;
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
