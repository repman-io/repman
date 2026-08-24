<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User;

use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;

class UserOAuthTokenProvider
{
    private UserRepository $repository;
    private UserOAuthTokenRefresher $tokenRefresher;

    public function __construct(UserRepository $repository, UserOAuthTokenRefresher $tokenRefresher)
    {
        $this->repository = $repository;
        $this->tokenRefresher = $tokenRefresher;
    }

    public function findAccessToken(string $userId, string $type): ?string
    {
        $token = $this->repository->getById(Uuid::fromString($userId))->oauthToken($type);
        if ($token === null) {
            return null;
        }

        // no flush: a refresh is committed by the store, on its own connection, and
        // leaves nothing pending on the entity
        return $token->accessToken($this->tokenRefresher);
    }
}
