<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\ResetPassword;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\User;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

final class ResetPasswordHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private EncoderFactoryInterface $encoderFactory;
    private int $resetPasswordTokenTtl;

    public function __construct(UserRepository $users, EncoderFactoryInterface $encoderFactory, int $resetPasswordTokenTtl)
    {
        $this->users = $users;
        $this->encoderFactory = $encoderFactory;
        $this->resetPasswordTokenTtl = $resetPasswordTokenTtl;
    }

    public function __invoke(ResetPassword $message): void
    {
        $this->users->getByResetPasswordToken($message->token())->resetPassword(
            $message->token(),
            $this->encoderFactory->getEncoder(User::class)->encodePassword($message->password(), null),
            $this->resetPasswordTokenTtl
        );
    }
}
