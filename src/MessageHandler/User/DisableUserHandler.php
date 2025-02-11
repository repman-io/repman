<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\DisableUser;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class DisableUserHandler implements MessageHandlerInterface
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function __invoke(DisableUser $message): void
    {
        $this
            ->users
            ->getById(Uuid::fromString($message->id()))
            ->disable();
    }
}
