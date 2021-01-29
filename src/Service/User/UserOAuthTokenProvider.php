<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User;

use Buddy\Repman\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class UserOAuthTokenProvider
{
    private UserRepository $repository;
    private EntityManagerInterface $em;
    private UserOAuthTokenRefresher $tokenRefresher;

    public function __construct(UserRepository $repository, EntityManagerInterface $em, UserOAuthTokenRefresher $tokenRefresher)
    {
        $this->repository = $repository;
        $this->em = $em;
        $this->tokenRefresher = $tokenRefresher;
    }

    public function findAccessToken(string $userId, string $type): ?string
    {
        $token = $this->repository->getById(Uuid::fromString($userId))->oauthToken($type);
        if ($token === null) {
            return null;
        }

        $accessToken = $token->accessToken($this->tokenRefresher);
        $this->em->flush();

        return $accessToken;
    }
}
