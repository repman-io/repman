<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\User;

use Buddy\Repman\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_api_token")
 */
class ApiToken
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
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\User", inversedBy="apiTokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct(string $value, string $name)
    {
        $this->value = $value;
        $this->name = $name;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
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
