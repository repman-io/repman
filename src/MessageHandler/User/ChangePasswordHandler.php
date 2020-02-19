<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\ChangePassword;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class ChangePasswordHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private UserPasswordEncoderInterface $encoder;

    public function __construct(UserRepository $users, UserPasswordEncoderInterface $encoder)
    {
        $this->users = $users;
        $this->encoder = $encoder;
    }

    public function __invoke(ChangePassword $message): void
    {
        $user = $this->users->getById(Uuid::fromString($message->userId()));
        $user->changePassword(
            $this->encoder->encodePassword($user, $message->plainPassword())
        );
    }
}
