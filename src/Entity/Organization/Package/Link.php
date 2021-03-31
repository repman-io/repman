<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization\Package;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\Organization\Package;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="organization_package_link",
 *     indexes={
 *      @ORM\Index(name="link_package_id_idx", columns={"package_id"}),
 *      @ORM\Index(name="link_target_idx", columns={"target"}),
 *     }
 * )
 */
class Link
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="uuid")
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization")
     * @ORM\JoinColumn(nullable=false)
     */
    private Organization $organization;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization\Package", inversedBy="links")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private Package $package;

    /**
     * @ORM\Column(type="string")
     */
    private string $type;

    /**
     * @ORM\Column(type="string")
     */
    private string $target;

    /**
     * @ORM\Column(name="`constraint`",type="string")
     */
    private string $constraint;

    private ?string $packageId;
    private ?string $packageName;

    private ?string $targetPackageId;

    public function __construct(
        UuidInterface $id,
        string $type,
        string $target,
        string $constraint,
        ?string $packageId = null,
        ?string $targetPackageId = null
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->target = $target;
        $this->constraint = $constraint;
        $this->packageId = $packageId;
        $this->targetPackageId = $targetPackageId;
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function target(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function constraint(): string
    {
        return $this->constraint;
    }

    public function setConstraint(string $constraint): self
    {
        $this->constraint = $constraint;

        return $this;
    }

    public function packageId(): ?string
    {
        return $this->packageId;
    }

    public function targetPackageId(): ?string
    {
        return $this->targetPackageId;
    }

    public function packageName(): ?string
    {
        return $this->packageName;
    }

    public function setPackageName(?string $packageName): self
    {
        $this->packageName = $packageName;

        return $this;
    }

    public function setOrganization(Organization $organization): void
    {
        if (isset($this->organization)) {
            throw new \RuntimeException('You can not change link organization');
        }
        $this->organization = $organization;
    }

    public function setPackage(Package $package): void
    {
        if (isset($this->package)) {
            throw new \RuntimeException('You can not change link package');
        }
        $this->package = $package;
    }
}
