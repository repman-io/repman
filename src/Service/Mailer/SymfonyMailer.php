<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Mailer;

use Buddy\Repman\Service\Mailer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class SymfonyMailer implements Mailer
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendPasswordResetLink(string $email, string $token, string $operatingSystem, string $browser): void
    {
        $this->mailer->send((new TemplatedEmail())
            ->from('repman@buddy.works')
            ->to($email)
            ->htmlTemplate('emails/password-reset.html.twig')
            ->context([
                'userEmail' => $email,
                'token' => $token,
                'operatingSystem' => $operatingSystem,
                'browser' => $browser,
            ])
        );
    }
}
