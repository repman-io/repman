<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\EnableUser;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class EnableUserHandler implements MessageHandlerInterface
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function __invoke(EnableUser $message): void
    {
        $this
            ->users
            ->getById(Uuid::fromString($message->id()))
            ->enable();
    }
}
