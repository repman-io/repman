<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\Organization;

use Buddy\Repman\Entity\Organization;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="organization_invitation",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="email_organization", columns={"email", "organization_id"})}
 * )
 */
class Invitation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", unique=true)
     */
    private string $token;

    /**
     * @ORM\Column(type="string", length=180)
     */
    private string $email;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\Organization", inversedBy="invitations")
     * @ORM\JoinColumn(nullable=false)
     */
    private Organization $organization;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private string $role;

    public function __construct(string $token, string $email, Organization $organization, string $role)
    {
        if (!in_array($role, Member::availableRoles(), true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported role: %s', $role));
        }
        $this->token = $token;
        $this->email = $email;
        $this->organization = $organization;
        $this->role = $role;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function role(): string
    {
        return $this->role;
    }
}
