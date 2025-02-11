<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\RemoveUser;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveUserHandler implements MessageHandlerInterface
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function __invoke(RemoveUser $message): void
    {
        $this
            ->users
            ->remove(Uuid::fromString($message->id()));
    }
}
