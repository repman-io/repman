<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\ChangeTimezone;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ChangeTimezoneHandler
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function __invoke(ChangeTimezone $message): void
    {
        $this->users
            ->getById(Uuid::fromString($message->userId()))
            ->changeTimezone($message->timezone());
    }
}
