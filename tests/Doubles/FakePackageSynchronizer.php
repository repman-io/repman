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
    private ?string $error = null;

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
        $this->error = null;
    }

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    public function synchronize(Package $package): void
    {
        if ($this->error) {
            $package->syncFailure($this->error);

            return;
        }

        $package->syncSuccess(
            $this->name,
            $this->description,
            $this->latestReleasedVersion,
            $this->latestReleaseDate
        );
    }
}
