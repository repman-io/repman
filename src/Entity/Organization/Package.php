<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization;

use Buddy\Repman\Entity\Organization;
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
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $lastSyncError = null;

    public function __construct(UuidInterface $id, string $type, string $url)
    {
        $this->id = $id;
        $this->type = $type;
        $this->repositoryUrl = $url;
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
        $this->name = $name;
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
}
