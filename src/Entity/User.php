<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="Buddy\Repman\Repository\UserRepository")
 * @ORM\Table(name="`user`")
 */
class User implements UserInterface
{
    const STATUS_ENABLED = 'enabled';

    const STATUS_DISABLED = 'disabled';

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private string $email;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @var array<string>
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private string $password;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $emailConfirmedAt = null;

    /**
     * @ORM\Column(type="string", unique=true)
     */
    private string $emailConfirmToken;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     */
    private ?string $resetPasswordToken = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $resetPasswordTokenCreatedAt = null;

    /**
     * @var Collection<int,Organization>|Organization[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\Organization", mappedBy="owner", orphanRemoval=true)
     */
    private Collection $organizations;

    /**
     * @ORM\Column(type="string")
     */
    private string $status = self::STATUS_ENABLED;

    /**
     * @param array<string> $roles
     */
    public function __construct(UuidInterface $id, string $email, string $emailConfirmToken, array $roles)
    {
        $this->id = $id;
        $this->email = $email;
        $this->emailConfirmToken = $emailConfirmToken;
        $this->roles = $roles;
        $this->createdAt = new \DateTimeImmutable();
        $this->organizations = new ArrayCollection();
    }

    public function setResetPasswordToken(string $token): void
    {
        $this->resetPasswordToken = $token;
        $this->resetPasswordTokenCreatedAt = new \DateTimeImmutable();
    }

    public function resetPassword(string $token, string $password, int $tokenTtl): void
    {
        if ($token !== $this->resetPasswordToken) {
            throw new \InvalidArgumentException('Invalid reset password token');
        }

        if ($this->resetPasswordTokenCreatedAt === null || $this->resetPasswordTokenCreatedAt->modify(sprintf('%s sec', $tokenTtl)) < new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('Token expired');
        }

        $this->password = $password;
        $this->resetPasswordToken = null;
        $this->resetPasswordTokenCreatedAt = null;
    }

    public function confirmEmail(string $token): void
    {
        if ($this->emailConfirmedAt !== null) {
            return;
        }

        if ($token !== $this->emailConfirmToken) {
            throw new \InvalidArgumentException('Invalid confirm e-mail token');
        }

        $this->emailConfirmedAt = new \DateTimeImmutable();
    }

    public function emailConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->emailConfirmedAt;
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return $this->getEmail();
    }

    /**
     * @return array<string>
     *
     * @see UserInterface
     */
    public function getRoles(): array
    {
        // deny all access
        if ($this->isDisabled()) {
            return [];
        }

        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return null
     *
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int,Organization>|Organization[]
     */
    public function getOrganizations(): Collection
    {
        return $this->organizations;
    }

    public function addOrganization(Organization $organization): self
    {
        if (!$this->organizations->contains($organization)) {
            $this->organizations[] = $organization;
            $organization->setOwner($this);
        }

        return $this;
    }

    public function disable(): self
    {
        $this->status = self::STATUS_DISABLED;

        return $this;
    }

    public function enable(): self
    {
        $this->status = self::STATUS_ENABLED;

        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_DISABLED;
    }

    public function changePassword(string $password): void
    {
        $this->password = $password;
    }
}
