<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User;

use Buddy\Repman\Service\User\OAuthTokenStore\StoredToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\AccessToken;
use Buddy\Repman\Service\User\UserOAuthTokenRefresher\MissingRefreshTokenException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Grant\RefreshToken;
use Ramsey\Uuid\UuidInterface;

class UserOAuthTokenRefresher
{
    private ClientRegistry $oauth;
    private OAuthTokenStore $store;

    public function __construct(ClientRegistry $oauth, OAuthTokenStore $store)
    {
        $this->oauth = $oauth;
        $this->store = $store;
    }

    /**
     * The refresh token is read from storage under the lock rather than taken from the
     * caller: with rotating refresh tokens, anything the caller is holding may already
     * have been spent and replaced by another process.
     */
    public function refresh(UuidInterface $id, string $type): AccessToken
    {
        return $this->store->refreshExclusively($id, function (StoredToken $stored) use ($type): ?AccessToken {
            // a concurrent worker may have refreshed the token while we waited for the
            // lock, in which case its refresh token is already spent - reuse its result
            if (!$stored->isExpired()) {
                return null;
            }

            $refreshToken = $stored->refreshToken();
            if ($refreshToken === null) {
                throw new MissingRefreshTokenException('Unable to refresh access token without refresh token');
            }

            $accessToken = $this->oauth->getClient($type)->getOAuth2Provider()
                ->getAccessToken(new RefreshToken(), ['refresh_token' => $refreshToken]);

            // a response with no expires_in is reported as the absent value it is; the
            // store is what decides what to record for it, being the only writer of
            // these columns and so the only place that decision cannot be bypassed
            $expires = $accessToken->getExpires();

            return new AccessToken(
                $accessToken->getToken(),
                $expires !== null ? (new \DateTimeImmutable())->setTimestamp($expires) : null,
                $accessToken->getRefreshToken()
            );
        });
    }
}
