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

    public function __construct(
        UuidInterface $id,
        Package $package,
        string $type,
        string $target,
        string $constraint
    ) {
        $this->id = $id;
        $this->package = $package;
        $this->type = $type;
        $this->target = $target;
        $this->constraint = $constraint;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function target(): string
    {
        return $this->target;
    }

    public function constraint(): string
    {
        return $this->constraint;
    }
}
