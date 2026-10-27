<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\RemoveOAuthToken;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RemoveOAuthTokenHandler
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function __invoke(RemoveOAuthToken $message): void
    {
        $user = $this->users->getById(Uuid::fromString($message->userId()));
        $user->removeOAuthToken($message->type());
    }
}
