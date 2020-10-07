<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity;

use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Entity\User\ApiToken;
use Buddy\Repman\Entity\User\OAuthToken;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="Buddy\Repman\Repository\UserRepository")
 * @ORM\Table(name="`user`")
 */
class User
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
     * @var Collection<int,Member>|Member[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\Organization\Member", mappedBy="user", orphanRemoval=true)
     */
    private Collection $memberships;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $status = self::STATUS_ENABLED;

    /**
     * @var Collection<int,OAuthToken>|OAuthToken[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\User\OAuthToken", mappedBy="user", orphanRemoval=true, cascade={"persist"})
     */
    private Collection $oauthTokens;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $emailScanResult = true;

    /**
     * @var Collection<int,ApiToken>|ApiToken[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\User\ApiToken", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $apiTokens;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $timezone;

    /**
     * @param array<string> $roles
     */
    public function __construct(UuidInterface $id, string $email, string $emailConfirmToken, array $roles, ?string $timezone = null)
    {
        $this->id = $id;
        $this->email = \mb_strtolower($email);
        $this->emailConfirmToken = $emailConfirmToken;
        $this->roles = array_values(array_unique($roles));
        $this->timezone = $timezone ?? date_default_timezone_get();
        $this->createdAt = new \DateTimeImmutable();
        $this->memberships = new ArrayCollection();
        $this->oauthTokens = new ArrayCollection();
        $this->apiTokens = new ArrayCollection();
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
     * @param string[] $roles
     */
    public function changeRoles(array $roles): void
    {
        $this->roles = array_values(array_unique($roles));
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return Collection<int,Organization>|Organization[]
     */
    public function getOrganizations(): Collection
    {
        return $this->memberships->map(fn (Member $member) => $member->organization());
    }

    public function disable(): void
    {
        $this->status = self::STATUS_DISABLED;
    }

    public function enable(): void
    {
        $this->status = self::STATUS_ENABLED;
    }

    public function changePassword(string $password): void
    {
        $this->password = $password;
    }

    public function addOAuthToken(OAuthToken $oauthToken): self
    {
        if (!$this->oauthTokens->contains($oauthToken)) {
            $this->oauthTokens[] = $oauthToken;
        }

        return $this;
    }

    public function oauthToken(string $type): ?OAuthToken
    {
        foreach ($this->oauthTokens as $token) {
            if ($token->isType($type)) {
                return $token;
            }
        }

        return null;
    }

    public function removeOAuthToken(string $type): void
    {
        foreach ($this->oauthTokens as $oauthToken) {
            if ($oauthToken->isType($type)) {
                $this->oauthTokens->removeElement($oauthToken);
            }
        }
    }

    public function addMembership(Member $member): void
    {
        if (!$this->memberships->filter(fn (Member $m) => $m->userId()->equals($member->userId()))->isEmpty()) {
            return;
        }

        $this->memberships->add($member);
    }

    public function emailScanResult(): bool
    {
        return $this->emailScanResult;
    }

    public function hasEmailConfirmed(): bool
    {
        return $this->emailConfirmedAt !== null;
    }

    public function setEmailScanResult(bool $emailScanResult): void
    {
        $this->emailScanResult = $emailScanResult;
    }

    public function addApiToken(ApiToken $token): void
    {
        $token->setUser($this);
        $this->apiTokens->add($token);
    }

    public function regenerateApiToken(string $value, string $newValue): void
    {
        foreach ($this->apiTokens as $token) {
            if ($token->isEqual($value)) {
                $token->regenerate($newValue);
            }
        }
    }

    public function removeApiToken(string $value): void
    {
        foreach ($this->apiTokens as $token) {
            if ($token->isEqual($value)) {
                $this->apiTokens->removeElement($token);
            }
        }
    }

    public function changeTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }
}
