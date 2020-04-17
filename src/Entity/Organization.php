<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity;

use Buddy\Repman\Entity\Organization\Invitation;
use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Token;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="Buddy\Repman\Repository\OrganizationRepository")
 */
class Organization
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\User", inversedBy="organizations")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $owner;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", unique=true, length=255)
     */
    private string $alias;

    /**
     * @var Collection<int,Package>|Package[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\Organization\Package", mappedBy="organization", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $packages;

    /**
     * @var Collection<int,Token>|Token[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\Organization\Token", mappedBy="organization", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $tokens;

    /**
     * @var Collection<int,Invitation>|Invitation[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\Organization\Invitation", mappedBy="organization", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $invitations;

    /**
     * @var Collection<int,Member>|Member[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\Organization\Member", mappedBy="organization", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $members;

    public function __construct(UuidInterface $id, User $owner, string $name, string $alias)
    {
        $this->id = $id;
        $this->setOwner($owner->addOrganization($this));
        $this->name = $name;
        $this->alias = $alias;
        $this->createdAt = new \DateTimeImmutable();
        $this->packages = new ArrayCollection();
        $this->tokens = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->members = new ArrayCollection();
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function owner(): User
    {
        return $this->owner;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    public function addToken(Token $token): void
    {
        if ($this->tokens->contains($token)) {
            return;
        }

        $token->setOrganization($this);
        $this->tokens->add($token);
    }

    public function regenerateToken(string $value, string $newValue): void
    {
        foreach ($this->tokens as $token) {
            if ($token->isEqual($value)) {
                $token->regenerate($newValue);
            }
        }
    }

    public function removeToken(string $value): void
    {
        foreach ($this->tokens as $token) {
            if ($token->isEqual($value)) {
                $this->tokens->removeElement($token);
            }
        }
    }

    /**
     * @return Collection<int,Package>|Package[]
     */
    public function synchronizedPackages(): Collection
    {
        return $this->packages->filter(fn ($package) => $package->isSynchronized());
    }

    public function addPackage(Package $package): void
    {
        if ($this->packages->contains($package)) {
            return;
        }

        $package->setOrganization($this);
        $this->packages->add($package);
    }

    public function removePackage(UuidInterface $packageId): void
    {
        foreach ($this->packages as $package) {
            if ($package->id()->equals($packageId)) {
                $this->packages->removeElement($package);
            }
        }
    }

    public function changeName(string $name): void
    {
        $this->name = $name;
    }

    public function changeAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    public function inviteUser(string $email, string $role, string $token): void
    {
        if ($this->invitations->exists(fn (int $key, Invitation $invitation) => $invitation->email() === $email)) {
            return;
        }

        if ($this->members->exists(fn (int $key, Member $member) => $member->email() === $email)) {
            return;
        }

        $this->invitations->add(new Invitation($token, $email, $this, $role));
    }

    public function acceptInvitation(string $token, User $user): void
    {
        $invitation = $this->invitations->filter(fn (Invitation $invitation) => $invitation->token() === $token)->first();
        if (!$invitation instanceof Invitation) {
            return;
        }

        $this->members->add(new Member(Uuid::uuid4(), $user, $this, $invitation->role()));
        $this->invitations->removeElement($invitation);
    }
}
