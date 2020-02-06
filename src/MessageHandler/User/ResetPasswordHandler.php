<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\ResetPassword;
use Buddy\Repman\Repository\UserRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class ResetPasswordHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private UserPasswordEncoderInterface $encoder;
    private int $resetPasswordTokenTtl;

    public function __construct(UserRepository $users, UserPasswordEncoderInterface $encoder, int $resetPasswordTokenTtl)
    {
        $this->users = $users;
        $this->encoder = $encoder;
        $this->resetPasswordTokenTtl = $resetPasswordTokenTtl;
    }

    public function __invoke(ResetPassword $message): void
    {
        $user = $this->users->getByResetPasswordToken($message->token());
        $user->resetPassword(
            $message->token(),
            $this->encoder->encodePassword($user, $message->password()),
            $this->resetPasswordTokenTtl
        );
    }
}
