<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User\OAuthToken;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="organization_package",
 *     uniqueConstraints={@UniqueConstraint(name="package_name", columns={"organization_id", "name"})}
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
     * @param mixed[] $metadata
     */
    public function __construct(UuidInterface $id, string $type, string $url, array $metadata = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->repositoryUrl = $url;
        $this->metadata = $metadata;
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

    public function syncSuccess(string $name, string $description, string $latestReleasedVersion, \DateTimeImmutable $latestReleaseDate): void
    {
        $this->setName($name);
        $this->description = $description;
        $this->latestReleasedVersion = $latestReleasedVersion;
        $this->latestReleaseDate = $latestReleaseDate;
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

    public function oauthToken(): string
    {
        $token = $this->organization->owner()->oauthToken(str_replace('-oauth', '', $this->type));
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
     * @return mixed
     */
    public function metadata(string $key)
    {
        if (!isset($this->metadata[$key])) {
            throw new \RuntimeException(sprintf('Metadata %s not found for project %s', $key, $this->id->toString()));
        }

        return $this->metadata[$key];
    }

    private function setName(string $name): void
    {
        if (preg_match(self::NAME_PATTERN, $name, $matches) !== 1) {
            throw new \RuntimeException("Package name {$name} is invalid");
        }

        $this->name = $name;
    }
}
