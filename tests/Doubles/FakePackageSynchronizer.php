<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Doubles;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Package\Link;
use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Query\User\Model\Package\Link as LinkModel;
use Buddy\Repman\Service\PackageSynchronizer;
use Ramsey\Uuid\Uuid;

final class FakePackageSynchronizer implements PackageSynchronizer
{
    private string $name = 'default/default';
    private string $description = 'n/a';
    private string $latestReleasedVersion = '1.0.0';
    private \DateTimeImmutable $latestReleaseDate;
    private ?string $error = null;
    private ?string $readme = null;
    private ?string $replacementPackage = null;

    /**
     * @var Version[]
     */
    private array $versions = [];

    /**
     * @var LinkModel[]
     */
    private array $links = [];

    public function __construct()
    {
        $this->latestReleaseDate = new \DateTimeImmutable();
    }

    /**
     * @param Version[]   $versions
     * @param LinkModel[] $links
     */
    public function setData(string $name, string $description, string $latestReleasedVersion, \DateTimeImmutable $latestReleaseDate, array $versions = [], array $links = [], ?string $readme = null, ?string $replacementPackage = null): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->latestReleasedVersion = $latestReleasedVersion;
        $this->latestReleaseDate = $latestReleaseDate;
        $this->error = null;
        $this->versions = $versions;
        $this->links = $links;
        $this->readme = $readme;
        $this->replacementPackage = $replacementPackage;
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

        $package->setReadme($this->readme);
        $package->setReplacementPackage($this->replacementPackage);
        $encounteredVersions = [];
        foreach ($this->versions as $version) {
            $package->addOrUpdateVersion($version);
            $encounteredVersions[$version->version()] = true;
        }

        $encounteredLinks = [];

        foreach ($this->links as $link) {
            $package->addLink(new Link(Uuid::uuid4(), $package, $link->type(), $link->target(), $link->constraint()));
            $encounteredLinks[$link->type().'-'.$link->target()] = true;
        }

        $package->syncSuccess(
            $this->name,
            $this->description,
            $this->latestReleasedVersion,
            $encounteredVersions,
            $encounteredLinks,
            $this->latestReleaseDate
        );
    }
}
