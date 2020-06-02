<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\ChangePassword;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\User;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

final class ChangePasswordHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private EncoderFactoryInterface $encoderFactory;

    public function __construct(UserRepository $users, EncoderFactoryInterface $encoderFactory)
    {
        $this->users = $users;
        $this->encoderFactory = $encoderFactory;
    }

    public function __invoke(ChangePassword $message): void
    {
        $this->users->getById(Uuid::fromString($message->userId()))->changePassword(
            $this->encoderFactory->getEncoder(User::class)->encodePassword($message->plainPassword(), null)
        );
    }
}
