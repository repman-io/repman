<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Service\PackageSynchronizer;

final class FakePackageSynchronizer implements PackageSynchronizer
{
    private string $name = 'default/default';
    private string $description = 'n/a';
    private string $latestReleasedVersion = '1.0.0';
    private \DateTimeImmutable $latestReleaseDate;
    private ?string $error = null;

    /**
     * @var Version[]
     */
    private array $versions = [];

    public function __construct()
    {
        $this->latestReleaseDate = new \DateTimeImmutable();
    }

    /**
     * @param Version[] $versions
     */
    public function setData(string $name, string $description, string $latestReleasedVersion, \DateTimeImmutable $latestReleaseDate, array $versions = []): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->latestReleasedVersion = $latestReleasedVersion;
        $this->latestReleaseDate = $latestReleaseDate;
        $this->error = null;
        $this->versions = $versions;
    }

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    public function synchronize(Package $package): void
    {
        if ($this->error !== null) {
            $package->syncFailure($this->error);

            return;
        }

        foreach ($this->versions as $version) {
            $package->addOrUpdateVersion($version);
        }

        $encounteredVersions = array_map(function (Version $version): string {
            return $version->version();
        }, $this->versions);

        $package->syncSuccess(
            $this->name,
            $this->description,
            $this->latestReleasedVersion,
            $encounteredVersions,
            $this->latestReleaseDate
        );
    }
}
