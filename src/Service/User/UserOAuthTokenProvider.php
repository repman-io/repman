<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class UserOAuthTokenProvider
{
    public function __construct(private readonly UserRepository $repository, private readonly EntityManagerInterface $em, private readonly UserOAuthTokenRefresher $tokenRefresher)
    {
    }

    public function findAccessToken(string $userId, string $type): ?string
    {
        $token = $this->repository->getById(Uuid::fromString($userId))->oauthToken($type);
        if (!$token instanceof OAuthToken) {
            return null;
        }

        $accessToken = $token->accessToken($this->tokenRefresher);
        $this->em->flush();

        return $accessToken;
    }
}
