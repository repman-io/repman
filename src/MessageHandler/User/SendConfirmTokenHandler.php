<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\SendConfirmToken;
use Buddy\Repman\Service\Mailer;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class SendConfirmTokenHandler implements MessageHandlerInterface
{
    public function __construct(private readonly Mailer $mailer)
    {
    }

    public function __invoke(SendConfirmToken $message): void
    {
        $this->mailer->sendEmailVerification($message->email(), $message->token());
    }
}
