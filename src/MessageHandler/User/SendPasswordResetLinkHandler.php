<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\SendPasswordResetLink;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Service\Mailer;
use Buddy\Repman\Service\User\ResetPasswordTokenGenerator;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class SendPasswordResetLinkHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private Mailer $mailer;
    private ResetPasswordTokenGenerator $generator;

    public function __construct(UserRepository $users, Mailer $mailer, ResetPasswordTokenGenerator $generator)
    {
        $this->users = $users;
        $this->mailer = $mailer;
        $this->generator = $generator;
    }

    public function __invoke(SendPasswordResetLink $message): void
    {
        $token = $this->generator->generate();
        $this->users->getByEmail($message->email())->setResetPasswordToken($token);
        $this->mailer->sendPasswordResetLink($message->email(), $token, $message->operatingSystem(), $message->browser());
    }
}
