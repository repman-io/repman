<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\User;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="user_oauth_token",
 *     uniqueConstraints={@UniqueConstraint(name="token_type", columns={"type", "user_id"})}
 * )
 */
class OauthToken
{
    const TYPE_GITHUB = 'github';
    const TYPE_GITLAB = 'gitlab';
    const TYPE_BITBUCKET = 'bitbucket';

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\User", inversedBy="oauthTokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="string", length=9)
     */
    private string $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $value;

    /**
     * @var Collection<int,Package>|Package[]
     * @ORM\OneToMany(targetEntity="Buddy\Repman\Entity\Organization\Package", mappedBy="oauthToken")
     */
    private Collection $packages;

    public function __construct(UuidInterface $id, User $user, string $type, string $value)
    {
        $this->id = $id;
        $this->setUser($user->addOauthToken($this));
        $this->type = $type;
        $this->value = $value;
        $this->createdAt = new \DateTimeImmutable();
        $this->packages = new ArrayCollection();
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isType(string $type): bool
    {
        return $this->type() === $type;
    }

    public function value(): string
    {
        return $this->value;
    }
}
