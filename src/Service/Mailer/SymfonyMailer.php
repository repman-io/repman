<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Mailer;

use Buddy\Repman\Service\Mailer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class SymfonyMailer implements Mailer
{
    private MailerInterface $mailer;
    private string $sender;

    public function __construct(MailerInterface $mailer, string $sender)
    {
        $this->mailer = $mailer;
        $this->sender = $sender;
    }

    public function sendPasswordResetLink(string $email, string $token, string $operatingSystem, string $browser): void
    {
        $this->mailer->send((new TemplatedEmail())
            ->from($this->sender)
            ->to($email)
            ->subject('Reset password to your Repman account')
            ->htmlTemplate('emails/password-reset.html.twig')
            ->context([
                'userEmail' => $email,
                'token' => $token,
                'operatingSystem' => $operatingSystem,
                'browser' => $browser,
            ])
        );
    }

    public function sendEmailVerification(string $email, string $token): void
    {
        $this->mailer->send((new TemplatedEmail())
            ->from($this->sender)
            ->to($email)
            ->subject('Verify your Repman email address')
            ->htmlTemplate('emails/email-verification.html.twig')
            ->context([
                'userEmail' => $email,
                'token' => $token,
            ])
        );
    }
}
