<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\User;

use Buddy\Repman\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="Buddy\Repman\Repository\OauthTokenRepository")
 * @ORM\Table(
 *     name="user_oauth_token",
 *     uniqueConstraints={@UniqueConstraint(name="token_type", columns={"type", "user_id"})}
 * )
 */
class OauthToken
{
    const TYPE_GITHUB = 'GitHub';
    const TYPE_GITLAB = 'GitLab';
    const TYPE_BITBUCKET = 'Bitbucket';

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     */
    private UuidInterface $id;

    /**
     * @ORM\ManyToOne(targetEntity="Buddy\Repman\Entity\User")
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

    public function __construct(UuidInterface $id, User $user, string $type, string $value)
    {
        $this->id = $id;
        $this->setUser($user->addOauthToken($this));
        $this->type = $type;
        $this->value = $value;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): UuidInterface
    {
        return $this->id;
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
