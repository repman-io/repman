<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization\Package;

use Buddy\Repman\Entity\Organization\Package;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="organization_package_version",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="package_version", columns={"package_id", "version"})},
 *     indexes={
 *      @ORM\Index(name="version_package_id_idx", columns={"package_id"}),
 *      @ORM\Index(name="version_date_idx", columns={"date"})
 *     }
 * )
 */
class Version
{
    const STABILITY_STABLE = 'stable';

    /**
     * @ORM\Id()
     * @ORM\Column(type="uuid")
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization\Package", inversedBy="versions")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private Package $package;

    /**
     * @ORM\Column(type="string")
     */
    private string $version;

    /**
     * @ORM\Column(type="string")
     */
    private string $reference;

    /**
     * @ORM\Column(type="integer")
     */
    private int $size;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $date;

    /**
     * @ORM\Column(type="string")
     *
     * (dev, alpha, beta, RC, stable)
     */
    private string $stability;

    public function __construct(
        UuidInterface $id,
        string $version,
        string $reference,
        int $size,
        \DateTimeImmutable $date,
        string $stability
    ) {
        $this->id = $id;
        $this->version = $version;
        $this->reference = $reference;
        $this->size = $size;
        $this->date = $date;
        $this->stability = $stability;
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function setDate(\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function setPackage(Package $package): void
    {
        if (isset($this->package)) {
            throw new \RuntimeException('You can not change version package');
        }
        $this->package = $package;
    }
}
