<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class CreateUserHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private UserPasswordEncoderInterface $encoder;

    public function __construct(UserRepository $users, UserPasswordEncoderInterface $encoder)
    {
        $this->users = $users;
        $this->encoder = $encoder;
    }

    public function __invoke(CreateUser $message): void
    {
        $user = new User(
            Uuid::fromString($message->id()),
            $message->email(),
            $message->confirmToken(),
            $message->roles()
        );
        $user->setPassword($this->encoder->encodePassword($user, $message->plainPassword()));

        $this->users->add($user);
    }
}
