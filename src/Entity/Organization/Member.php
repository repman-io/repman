<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="organization_member",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="user_organization", columns={"user_id", "organization_id"})}
 * )
 */
class Member
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_MEMBER = 'member';

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\User", inversedBy="memberships")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization", inversedBy="members")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private Organization $organization;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private string $role;

    public function __construct(UuidInterface $id, User $user, Organization $organization, string $role)
    {
        if (!in_array($role, self::availableRoles(), true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported role: %s', $role));
        }

        $this->id = $id;
        $this->user = $user;
        $this->organization = $organization;
        $this->role = $role;
    }

    public function changeRole(string $role): void
    {
        if (!in_array($role, self::availableRoles(), true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported role: %s', $role));
        }

        $this->role = $role;
    }

    public function email(): string
    {
        return $this->user->getEmail();
    }

    public function userId(): UuidInterface
    {
        return $this->user->id();
    }

    public function user(): User
    {
        return $this->user;
    }

    public function organization(): Organization
    {
        return $this->organization;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * @return array<int,string>
     */
    public static function availableRoles(): array
    {
        return [
            self::ROLE_MEMBER,
            self::ROLE_OWNER,
        ];
    }

    public function emailScanResult(): bool
    {
        return $this->user->emailScanResult();
    }

    public function hasEmailConfirmed(): bool
    {
        return $this->user->hasEmailConfirmed();
    }
}
