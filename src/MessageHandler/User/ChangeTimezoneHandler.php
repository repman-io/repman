<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\ChangeTimezone;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ChangeTimezoneHandler implements MessageHandlerInterface
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function __invoke(ChangeTimezone $message): void
    {
        $this->users
            ->getById(Uuid::fromString($message->userId()))
            ->changeTimezone($message->timezone());
    }
}
