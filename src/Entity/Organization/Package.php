<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Package\Version;
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
     * @param mixed[] $metadata
     */
    public function __construct(UuidInterface $id, string $type, string $url, array $metadata = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->repositoryUrl = $url;
        $this->metadata = $metadata;
        $this->versions = new ArrayCollection();
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
     * @param string[] $encounteredVersions
     */
    public function syncSuccess(string $name, string $description, string $latestReleasedVersion, array $encounteredVersions, \DateTimeImmutable $latestReleaseDate): void
    {
        $this->setName($name);
        $this->description = $description;
        $this->latestReleasedVersion = $latestReleasedVersion;
        $this->latestReleaseDate = $latestReleaseDate;
        foreach ($this->versions as $version) {
            if (!in_array($version->version(), $encounteredVersions, true)) {
                $this->versions->removeElement($version);
            }
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

    public function oauthToken(): string
    {
        $token = $this->organization->oauthToken(str_replace('-oauth', '', $this->type));
        if ($token === null) {
            throw new \RuntimeException('Oauth token not found');
        }

        return $token->accessToken();
    }

    public function hasOAuthToken(): bool
    {
        return strpos($this->type, 'oauth') !== false;
    }

    public function webhookWasCreated(): void
    {
        $this->webhookCreatedAt = new \DateTimeImmutable();
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
     * @return Version|false
     */
    public function getVersion(string $versionString)
    {
        return $this->versions->filter(fn (Version $version) => $version->version() === $versionString)->first();
    }
}
