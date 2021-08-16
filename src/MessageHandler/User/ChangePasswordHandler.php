<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\ChangePassword;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\User;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class ChangePasswordHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private PasswordHasherFactoryInterface $hasherFactory;

    public function __construct(UserRepository $users, PasswordHasherFactoryInterface $hasherFactory)
    {
        $this->users = $users;
        $this->hasherFactory = $hasherFactory;
    }

    public function __invoke(ChangePassword $message): void
    {
        $this->users->getById(Uuid::fromString($message->userId()))->changePassword(
            $this->hasherFactory->getPasswordHasher(User::class)->hash($message->plainPassword())
        );
    }
}
