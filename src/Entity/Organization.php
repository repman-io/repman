<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity;

use Buddy\Repman\Entity\Organization\Invitation;
use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Token;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Repository\OrganizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
class Organization
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private UuidInterface $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', unique: true, length: 255)]
    private string $alias;

    /**
     * @var Collection<int, Package>
     */
    #[ORM\OneToMany(targetEntity: Package::class, mappedBy: 'organization', cascade: ['persist'], orphanRemoval: true)]
    private Collection $packages;

    /**
     * @var Collection<int, Token>
     */
    #[ORM\OneToMany(targetEntity: Token::class, mappedBy: 'organization', cascade: ['persist'], orphanRemoval: true)]
    private Collection $tokens;

    /**
     * @var Collection<int, Invitation>
     */
    #[ORM\OneToMany(targetEntity: Invitation::class, mappedBy: 'organization', cascade: ['persist'], orphanRemoval: true)]
    private Collection $invitations;

    /**
     * @var Collection<int, Member>
     */
    #[ORM\OneToMany(targetEntity: Member::class, mappedBy: 'organization', cascade: ['persist'], orphanRemoval: true)]
    private Collection $members;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $hasAnonymousAccess = false;

    public function __construct(UuidInterface $id, User $owner, string $name, string $alias)
    {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
        $this->createdAt = new \DateTimeImmutable();
        $this->packages = new ArrayCollection();
        $this->tokens = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->members->add($member = new Member(Uuid::uuid4(), $owner, $this, Member::ROLE_OWNER));
        $owner->addMembership($member);
    }

    public function id(): UuidInterface
    {
        return $this->id;
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
     * @return Collection<int, Package>
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

    public function inviteUser(string $email, string $role, string $token): bool
    {
        if ($this->invitations->exists(fn (int $key, Invitation $invitation) => $invitation->email() === $email)) {
            return false;
        }

        if ($this->members->exists(fn (int $key, Member $member) => $member->email() === $email)) {
            return false;
        }

        $this->invitations->add(new Invitation($token, $email, $this, $role));

        return true;
    }

    public function removeInvitation(string $token): void
    {
        foreach ($this->invitations as $invitation) {
            if ($invitation->token() === $token) {
                $this->invitations->removeElement($invitation);
                break;
            }
        }
    }

    public function acceptInvitation(string $token, User $user): void
    {
        $invitation = $this->invitations->filter(fn (Invitation $invitation) => $invitation->token() === $token)->first();
        if (!$invitation instanceof Invitation) {
            return;
        }

        if ($invitation->email() !== $user->getEmail()) {
            return;
        }

        $this->members->add(new Member(Uuid::uuid4(), $user, $this, $invitation->role()));
        $this->invitations->removeElement($invitation);
    }

    public function removeMember(User $user): void
    {
        if ($this->isLastOwner($user)) {
            throw new \RuntimeException('Organisation must have at least one owner.');
        }

        foreach ($this->members as $member) {
            if ($member->userId()->equals($user->id())) {
                $this->members->removeElement($member);
                break;
            }
        }
    }

    public function changeRole(User $user, string $role): void
    {
        if ($this->isLastOwner($user) && $role === Member::ROLE_MEMBER) {
            throw new \RuntimeException('Organisation must have at least one owner.');
        }

        foreach ($this->members as $member) {
            if ($member->userId()->equals($user->id())) {
                $member->changeRole($role);
                break;
            }
        }
    }

    /**
     * The token this organization's packages are synchronized with. Any of its owners
     * may have authorized one, and exactly one of them is used.
     *
     * Which one has to be stable. $members carries no @ORM\OrderBy, so owners arrive in
     * whatever order Postgres returns for an unordered select - an order that changes
     * after any update to organization_member. Returning the first owner holding a token
     * therefore made an organization with several authorized owners synchronize against
     * a different account from one run to the next: a dead token kept resurfacing however
     * many times the affected owner re-authorized, and every run spent a rotation of
     * whichever refresh token it happened to land on.
     *
     * The newest authorization wins. It is the most recent statement of which account to
     * use, and with rotating refresh tokens it is also the one whose token has had the
     * fewest opportunities to be lost.
     */
    public function oauthToken(string $type): ?OAuthToken
    {
        $tokens = [];
        foreach ($this->members->filter(fn (Member $member) => $member->isOwner()) as $owner) {
            $token = $owner->user()->oauthToken($type);
            if ($token !== null) {
                $tokens[] = $token;
            }
        }

        usort($tokens, function (OAuthToken $a, OAuthToken $b): int {
            $byAge = $b->createdAt() <=> $a->createdAt();

            // created_at is stored to the second, so two owners can authorize within the
            // same one - the id keeps the choice from falling back on row order again
            return $byAge !== 0 ? $byAge : strcmp($b->id()->toString(), $a->id()->toString());
        });

        return $tokens[0] ?? null;
    }

    /**
     * @return Collection<int, Member>
     */
    public function members(): Collection
    {
        return $this->members;
    }

    public function changeAnonymousAccess(bool $hasAnonymousAccess): void
    {
        $this->hasAnonymousAccess = $hasAnonymousAccess;
    }

    private function isLastOwner(User $user): bool
    {
        $owners = $this->members->filter(fn (Member $member) => $member->isOwner());
        if ($owners->count() > 1) {
            return false;
        }
        /** @var Member $lastOwner */
        $lastOwner = $owners->first();

        return $lastOwner->userId()->equals($user->id());
    }
}
