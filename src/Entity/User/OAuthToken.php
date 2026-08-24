<?php

declare(strict_types=1);

namespace Buddy\Repman\Entity\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\AccessToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\MissingRefreshTokenException;
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
     * Treat a token that is about to expire as expired, to leave room for the request
     * it is about to be used for.
     */
    public const EXPIRATION_MARGIN = '-1 min';

    /**
     * How long a token the provider has just handed over is used before it is refreshed
     * again, whatever its stated expiry says.
     *
     * The margin above is a hint to refresh early, and on its own it can ask for the
     * impossible: a token that arrives with an expires_in of a minute or less is inside
     * the margin the moment it is issued, so it counts as expired immediately and every
     * following call refreshes again - and each refresh is a provider round trip, a
     * connection, the row lock, and one more rotation of the refresh token.
     * ComposerPackageSynchronizer asks for a token twice per package version, so a
     * package with a few hundred of them would do that a few hundred times over.
     *
     * A freshly issued token is also the best one obtainable, so asking again straight
     * away cannot improve on it - it can only spend another rotation.
     *
     * The floor covers a token that is inside the margin, which is a hint to refresh
     * early rather than a statement that the token is dead. One that is genuinely past
     * its expiry is a different matter, and accessToken() reports it rather than
     * holding it for the rest of the interval.
     */
    private const MIN_REFRESH_INTERVAL = '-30 sec';

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
     * @ORM\Column(type="text")
     */
    private string $accessToken;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $refreshToken = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $expiresAt = null;

    /**
     * Deliberately not an ORM column. The store is the sole writer of the token
     * columns, and it writes under a row lock after re-reading them. Assigning a
     * refreshed token back onto the mapped fields would make the UnitOfWork a second
     * writer: the next flush would push these values out on the default connection,
     * with no lock and no re-read, overwriting a rotation another process committed
     * in the meantime - and a rotated refresh token, once overwritten, is gone.
     */
    private ?AccessToken $refreshed = null;

    /**
     * When the token above was obtained. Not an ORM column either, and deliberately not
     * shared with anything: it exists only to keep this instance from asking again the
     * moment it has asked.
     */
    private ?\DateTimeImmutable $refreshedAt = null;

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

    public function id(): UuidInterface
    {
        return $this->id;
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
        if ($this->refreshed !== null) {
            if (!self::hasExpired($this->refreshed->expiresAt())) {
                return $this->refreshed->token();
            }

            if ($this->wasJustRefreshed()) {
                return $this->justRefreshedToken();
            }
        } elseif (!self::hasExpired($this->expiresAt)) {
            return $this->accessToken;
        }

        try {
            // No check for a missing refresh token here: this copy of it may be stale,
            // and the refresher has to read the stored one under the lock anyway, so
            // that is where the condition is decided.
            $this->refreshed = $tokenRefresher->refresh($this->id, $this->type);
            $this->refreshedAt = new \DateTimeImmutable();
        } catch (MissingRefreshTokenException $exception) {
            // a token that cannot be refreshed at all is a data-state problem rather
            // than a transient failure, and stays distinguishable as one. Matched on its
            // own type rather than on \LogicException, which would also let through the
            // \InvalidArgumentException the OAuth and HTTP libraries throw on malformed
            // responses - losing the framing every other failure here carries.
            throw $exception;
        } catch (\Throwable $exception) {
            throw new \RuntimeException('An error occurred while refreshing the access token: '.$exception->getMessage(), 0, $exception);
        }

        return $this->refreshed->token();
    }

    /**
     * The token obtained within the last MIN_REFRESH_INTERVAL, for the case where the
     * margin already counts it as expired.
     *
     * Being inside the margin is a hint to refresh early, not a statement that the
     * token is dead, so it is still the best one available and is handed back. Being
     * genuinely past its expiry is different: serving it would turn every call for the
     * rest of the interval into an opaque 401 from the provider, and asking for another
     * one is what the interval exists to prevent - the provider handed this one over
     * moments ago, so a second request can only spend another rotation of the refresh
     * token. Say what happened instead; once the interval passes, the ordinary refresh
     * takes over again.
     */
    private function justRefreshedToken(): string
    {
        /** @var AccessToken $refreshed */
        $refreshed = $this->refreshed;
        $expiresAt = $refreshed->expiresAt();

        if ($expiresAt !== null && (new \DateTimeImmutable()) > $expiresAt) {
            $message = sprintf(
                'The %s provider returned an access token that had already expired at %s',
                $this->type,
                $expiresAt->format(\DateTimeInterface::ATOM)
            );

            throw new \RuntimeException($message);
        }

        return $refreshed->token();
    }

    private function wasJustRefreshed(): bool
    {
        return $this->refreshedAt !== null
            && $this->refreshedAt > (new \DateTimeImmutable())->modify(self::MIN_REFRESH_INTERVAL);
    }

    private static function hasExpired(?\DateTimeImmutable $expiresAt): bool
    {
        return $expiresAt !== null && (new \DateTimeImmutable()) > $expiresAt->modify(self::EXPIRATION_MARGIN);
    }
}
