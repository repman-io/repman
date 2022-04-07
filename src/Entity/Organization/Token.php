<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization;

use Buddy\Repman\Entity\Organization;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;

/**
 * @ORM\Entity
 * @ORM\Table(name="organization_token")
 */
class Token
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private string $value;

    /**
     * @ORM\Column(type="string")
     */
    private string $name;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization", inversedBy="tokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private Organization $organization;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $lastUsedAt = null;

    public function __construct(string $value, string $name)
    {
        $this->value = $value;
        $this->name = $name;
        $this->createdAt = new DateTimeImmutable();
    }

    public function setOrganization(Organization $organization): void
    {
        if (isset($this->organization)) {
            throw new RuntimeException('You can not change token organization');
        }
        $this->organization = $organization;
    }

    public function regenerate(string $value): void
    {
        $this->value = $value;
        $this->lastUsedAt = null;
    }

    public function isEqual(string $value): bool
    {
        return $this->value === $value;
    }
}
