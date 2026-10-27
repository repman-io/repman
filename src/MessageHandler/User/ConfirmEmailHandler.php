<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\ConfirmEmail;
use Buddy\Repman\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ConfirmEmailHandler
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function __invoke(ConfirmEmail $message): void
    {
        $this->users->getByConfirmEmailToken($message->token())->confirmEmail($message->token());
    }
}
