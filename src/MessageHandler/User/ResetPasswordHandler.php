<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\ResetPassword;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\User;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class ResetPasswordHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private PasswordHasherFactoryInterface $hasherFactory;
    private int $resetPasswordTokenTtl;

    public function __construct(UserRepository $users, PasswordHasherFactoryInterface $hasherFactory, int $resetPasswordTokenTtl)
    {
        $this->users = $users;
        $this->hasherFactory = $hasherFactory;
        $this->resetPasswordTokenTtl = $resetPasswordTokenTtl;
    }

    public function __invoke(ResetPassword $message): void
    {
        $this->users->getByResetPasswordToken($message->token())->resetPassword(
            $message->token(),
            $this->hasherFactory->getPasswordHasher(User::class)->hash($message->password()),
            $this->resetPasswordTokenTtl
        );
    }
}
