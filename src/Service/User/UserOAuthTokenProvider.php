<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\User;

use Buddy\Repman\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Ramsey\Uuid\Uuid;

class UserOAuthTokenProvider
{
    private UserRepository $repository;
    private EntityManagerInterface $em;
    private ClientRegistry $oauth;

    public function __construct(UserRepository $repository, EntityManagerInterface $em, ClientRegistry $oauth)
    {
        $this->repository = $repository;
        $this->em = $em;
        $this->oauth = $oauth;
    }

    public function findAccessToken(string $userId, string $type): ?string
    {
        $token = $this->repository->getById(Uuid::fromString($userId))->oauthToken($type);
        if ($token === null) {
            return null;
        }

        $accessToken = $token->accessToken($this->oauth);
        $this->em->flush();

        return $accessToken;
    }
}
