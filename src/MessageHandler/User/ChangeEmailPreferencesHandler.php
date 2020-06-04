<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\ChangeEmailPreferences;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ChangeEmailPreferencesHandler implements MessageHandlerInterface
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function __invoke(ChangeEmailPreferences $message): void
    {
        $this->users->setEmailScanResult(
            Uuid::fromString($message->userId()),
            $message->emailScanResult()
        );
    }
}
