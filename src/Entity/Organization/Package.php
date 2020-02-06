<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization;

use Buddy\Repman\Entity\Organization;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="organization_package")
 */
class Package
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\Column(type="string", unique=true, length=255)
     */
    private string $name;

    /**
     * @ORM\Column(type="text")
     */
    private string $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $latestReleasedVersion;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $latestReleaseDate;

    /**
     * @ORM\Column(type="text")
     */
    private string $repositoryUrl;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization", inversedBy="packages")
     * @ORM\JoinColumn(nullable=false)
     */
    private Organization $organization;

    public function __construct(UuidInterface $id, string $url, string $name, string $description, string $latestReleasedVersion)
    {
        $this->id = $id;
        $this->repositoryUrl = $url;
        $this->name = $name;
        $this->description = $description;
        $this->latestReleasedVersion = $latestReleasedVersion;
        $this->latestReleaseDate = new \DateTimeImmutable();
    }

    public function setOrganization(Organization $organization): void
    {
        if (isset($this->organization)) {
            throw new \RuntimeException('You can not change package organization');
        }
        $this->organization = $organization;
    }
}
