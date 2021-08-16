<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\User as SecurityUser;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class CreateUserHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private PasswordHasherFactoryInterface $hasherFactory;

    public function __construct(UserRepository $users, PasswordHasherFactoryInterface $hasherFactory)
    {
        $this->users = $users;
        $this->hasherFactory = $hasherFactory;
    }

    public function __invoke(CreateUser $message): void
    {
        $user = new User(
            Uuid::fromString($message->id()),
            $message->email(),
            $message->confirmToken(),
            $message->roles()
        );
        $user->setPassword($this->hasherFactory->getPasswordHasher(SecurityUser::class)->hash($message->plainPassword()));

        $this->users->add($user);
    }
}
