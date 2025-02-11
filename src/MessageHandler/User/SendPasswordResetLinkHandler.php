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
    public function __construct(private readonly UserRepository $users, private readonly Mailer $mailer, private readonly ResetPasswordTokenGenerator $generator)
    {
    }

    public function __invoke(SendPasswordResetLink $message): void
    {
        // return, to prevent checking if account exist for given email (security)
        if (!$this->users->emailExist($message->email())) {
            return;
        }

        $token = $this->generator->generate();
        $this->users->getByEmail($message->email())->setResetPasswordToken($token);
        $this->mailer->sendPasswordResetLink($message->email(), $token, $message->operatingSystem(), $message->browser());
    }
}
