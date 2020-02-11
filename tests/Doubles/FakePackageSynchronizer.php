<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Service\PackageSynchronizer;

final class FakePackageSynchronizer implements PackageSynchronizer
{
    private string $name = 'default';
    private string $description = 'n/a';
    private string $latestReleasedVersion = '1.0.0';
    private \DateTimeImmutable $latestReleaseDate;

    public function __construct()
    {
        $this->latestReleaseDate = new \DateTimeImmutable();
    }

    public function setData(string $name, string $description, string $latestReleasedVersion, \DateTimeImmutable $latestReleaseDate): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->latestReleasedVersion = $latestReleasedVersion;
        $this->latestReleaseDate = $latestReleaseDate;
    }

    public function synchronize(Package $package): void
    {
        $package->synchronize(
            $this->name,
            $this->description,
            $this->latestReleasedVersion,
            $this->latestReleaseDate
        );
    }
}
