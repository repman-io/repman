<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher;
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
class OAuthToken
{
    public const TYPE_GITHUB = 'github';
    public const TYPE_GITLAB = 'gitlab';
    public const TYPE_BITBUCKET = 'bitbucket';

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
    private string $accessToken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $refreshToken = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct(
        UuidInterface $id,
        User $user,
        string $type,
        string $accessToken,
        ?string $refreshToken = null,
        ?\DateTimeImmutable $expiresAt = null
    ) {
        $this->id = $id;
        $this->user = $user->addOAuthToken($this);
        $this->type = $type;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isType(string $type): bool
    {
        return $this->type() === $type;
    }

    public function accessToken(UserOAuthTokenRefresher $tokenRefresher): string
    {
        if ($this->expiresAt !== null && (new \DateTimeImmutable()) > $this->expiresAt->modify('-1 min')) {
            if ($this->refreshToken === null) {
                throw new \LogicException('Unable to refresh access token without refresh token');
            }

            try {
                $newToken = $tokenRefresher->refresh($this->type, $this->refreshToken);
                $this->accessToken = $newToken->token();
                $this->expiresAt = $newToken->expiresAt();
            } catch (\Throwable $exception) {
                throw new \RuntimeException('An error occurred while refreshing the access token: '.$exception->getMessage());
            }
        }

        return $this->accessToken;
    }
}
