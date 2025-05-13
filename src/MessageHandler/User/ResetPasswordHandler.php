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
    public function __construct(private readonly UserRepository $users, private readonly PasswordHasherFactoryInterface $hasherFactory, private readonly int $resetPasswordTokenTtl)
    {
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
