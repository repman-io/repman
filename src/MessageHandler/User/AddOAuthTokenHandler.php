<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Message\User\AddOAuthToken;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AddOAuthTokenHandler implements MessageHandlerInterface
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function __invoke(AddOAuthToken $message): void
    {
        $user = $this->users->getById(Uuid::fromString($message->userId()));
        $user->addOAuthToken(
            new OAuthToken(
                Uuid::fromString($message->id()),
                $user,
                $message->type(),
                $message->accessToken(),
                $message->refreshToken(),
                $message->expiresAt()
            )
        );
    }
}
