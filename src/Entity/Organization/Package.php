<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Package\Link;
use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Entity\User\OAuthToken;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="organization_package",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="package_name", columns={"organization_id", "name"})}
 * )
 */
class Package
{
    const NAME_PATTERN = '/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9]([_.-]?[a-z0-9]+)*$/';

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $latestReleasedVersion = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $latestReleaseDate = null;

    /**
     * @ORM\Column(type="text")
     */
    private string $repositoryUrl;

    /**
     * @ORM\Column(type="string")
     */
    private string $type;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization", inversedBy="packages")
     * @ORM\JoinColumn(nullable=false)
     */
    private Organization $organization;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeInterface $lastSyncAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $webhookCreatedAt = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $webhookCreatedError = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $lastSyncError = null;

    /**
     * @ORM\Column(type="json")
     *
     * @var mixed[]
     */
    private array $metadata;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $readme = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     *
     * @var mixed[]
     */
    private ?array $lastScanResult = null;

    /**
     * @ORM\Column(type="string", length=7, nullable=true)
     */
    private ?string $lastScanStatus = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $lastScanDate = null;

    /**
     * @var Collection<int,Version>|Version[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\Organization\Package\Version", mappedBy="package", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $versions;

    /**
     * @var Collection<int,Link>|Link[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\Organization\Package\Link", mappedBy="package", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $links;

    /**
     * @ORM\Column(type="integer")
     */
    private int $keepLastReleases = 0;

    /**
     * @ORM\Column(type="boolean", options={"default":"true"})
     */
    private bool $enableSecurityScan = true;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $replacementPackage = null;

    /**
     * @param mixed[] $metadata
     */
    public function __construct(UuidInterface $id, string $type, string $url, array $metadata = [], int $keepLastReleases = 0, bool $enableSecurityScan = true)
    {
        $this->id = $id;
        $this->type = $type;
        $this->repositoryUrl = $url;
        $this->metadata = $metadata;
        $this->keepLastReleases = $keepLastReleases;
        $this->enableSecurityScan = $enableSecurityScan;
        $this->versions = new ArrayCollection();
        $this->links = new ArrayCollection();
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function setOrganization(Organization $organization): void
    {
        if (isset($this->organization)) {
            throw new \RuntimeException('You can not change package organization');
        }
        $this->organization = $organization;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function repositoryUrl(): string
    {
        return $this->repositoryUrl;
    }

    /**
     * @param array<string,bool> $encounteredVersions
     * @param array<string,bool> $encounteredLinks
     */
    public function syncSuccess(string $name, string $description, string $latestReleasedVersion, array $encounteredVersions, array $encounteredLinks, \DateTimeImmutable $latestReleaseDate): void
    {
        $this->setName($name);
        $this->description = $description;
        $this->latestReleasedVersion = $latestReleasedVersion;
        $this->latestReleaseDate = $latestReleaseDate;
        foreach ($this->versions as $key => $version) {
            if (!isset($encounteredVersions[$version->version()])) {
                $this->versions->remove($key);
            }
        }

        $uniqueLinks = [];
        foreach ($this->links as $key => $link) {
            $uniqueKey = $link->type().'-'.$link->target();
            if (!isset($encounteredLinks[$uniqueKey])) {
                $this->links->remove($key);
                continue;
            }

            if (!isset($uniqueLinks[$uniqueKey])) {
                $uniqueLinks[$uniqueKey] = true;
                continue;
            }

            $this->links->remove($key);
        }
        $this->lastSyncAt = new \DateTimeImmutable();
        $this->lastSyncError = null;
    }

    public function syncFailure(string $error): void
    {
        $this->lastSyncAt = new \DateTimeImmutable();
        $this->lastSyncError = $error;
    }

    public function organizationId(): UuidInterface
    {
        return $this->organization->id();
    }

    public function organizationAlias(): string
    {
        return $this->organization->alias();
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function isSynchronized(): bool
    {
        return $this->name !== null;
    }

    public function isSynchronizedSuccessfully(): bool
    {
        return $this->isSynchronized() && $this->lastSyncError === null;
    }

    public function oauthToken(): OAuthToken
    {
        $token = $this->organization->oauthToken(str_replace('-oauth', '', $this->type));
        if ($token === null) {
            throw new \RuntimeException('Oauth token not found');
        }

        return $token;
    }

    public function hasOAuthToken(): bool
    {
        return strpos($this->type, 'oauth') !== false;
    }

    public function webhookWasCreated(): void
    {
        $this->webhookCreatedAt = new \DateTimeImmutable();
        $this->webhookCreatedError = null;
    }

    public function webhookWasNotCreated(string $error): void
    {
        $this->webhookCreatedError = substr($error, 0, 255);
    }

    public function webhookWasRemoved(): void
    {
        $this->webhookCreatedAt = null;
    }

    /**
     * @return string[]
     */
    public function scanResultEmails(): array
    {
        return $this->organization->members()
            ->filter(fn ($member) => $member->emailScanResult() && $member->hasEmailConfirmed())
            ->map(fn ($member) => $member->email())
            ->toArray();
    }

    /**
     * @return mixed
     */
    public function metadata(string $key)
    {
        if (!isset($this->metadata[$key])) {
            throw new \RuntimeException(sprintf('Metadata %s not found for project %s', $key, $this->id->toString()));
        }

        return $this->metadata[$key];
    }

    public function readme(): ?string
    {
        return $this->readme;
    }

    public function setReadme(?string $readme): void
    {
        $this->readme = $readme;
    }

    public function latestReleasedVersion(): ?string
    {
        return $this->latestReleasedVersion;
    }

    /**
     * @param mixed[] $result
     */
    public function setScanResult(string $status, \DateTimeImmutable $date, array $result): void
    {
        $this->lastScanDate = $date;
        $this->lastScanStatus = $status;
        $this->lastScanResult = $result;
    }

    private function setName(string $name): void
    {
        if (preg_match(self::NAME_PATTERN, $name, $matches) !== 1) {
            throw new \RuntimeException("Package name {$name} is invalid");
        }

        $this->name = $name;
    }

    /**
     * @return Collection<int,Version>|Version[]
     */
    public function versions(): Collection
    {
        return $this->versions;
    }

    public function addOrUpdateVersion(Version $version): void
    {
        if ($this->getVersion($version->version()) !== false) {
            $this->getVersion($version->version())->setReference($version->reference());
            $this->getVersion($version->version())->setSize($version->size());
            $this->getVersion($version->version())->setDate($version->date());

            return;
        }

        $version->setPackage($this);
        $this->versions->add($version);
    }

    /**
     * @return Collection<int,Link>|Link[]
     */
    public function links(): Collection
    {
        return $this->links;
    }

    public function addLink(Link $link): void
    {
        $this->links->add($link);
    }

    public function removeVersion(Version $version): void
    {
        $this->versions->removeElement($version);
    }

    /**
     * @return Version|false
     */
    public function getVersion(string $versionString)
    {
        return $this->versions->filter(fn (Version $version) => $version->version() === $versionString)->first();
    }

    public function keepLastReleases(): int
    {
        return $this->keepLastReleases;
    }

    public function update(string $url, int $keepLastReleases, bool $enableSecurityScan): void
    {
        $this->keepLastReleases = $keepLastReleases;
        $this->repositoryUrl = $url;
        $this->enableSecurityScan = $enableSecurityScan;
    }

    public function getReplacementPackage(): ?string
    {
        return $this->replacementPackage;
    }

    public function setReplacementPackage(?string $replacementPackage): void
    {
        $this->replacementPackage = $replacementPackage;
    }

    public function isEnabledSecurityScan(): bool
    {
        return $this->enableSecurityScan;
    }

    public function setEnabledSecurityScan(bool $enableSecurityScan): void
    {
        $this->enableSecurityScan = $enableSecurityScan;
    }
}
