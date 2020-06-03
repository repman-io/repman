<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Mailer;

use Buddy\Repman\Service\Mailer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

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
            ->from(Address::fromString($this->sender))
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
            ->from(Address::fromString($this->sender))
            ->to($email)
            ->subject('Verify your Repman email address')
            ->htmlTemplate('emails/email-verification.html.twig')
            ->context([
                'userEmail' => $email,
                'token' => $token,
            ])
        );
    }

    public function sendInvitationToOrganization(string $email, string $token, string $organizationName): void
    {
        $this->mailer->send((new TemplatedEmail())
            ->from(Address::fromString($this->sender))
            ->to($email)
            ->subject(sprintf('You\'ve been invited to %s organization', $organizationName))
            ->htmlTemplate('emails/organization-invitation.html.twig')
            ->context([
                'userEmail' => $email,
                'token' => $token,
                'organizationName' => $organizationName,
            ])
        );
    }

    /**
     * @param string[] $emails
     * @param mixed[]  $result
     */
    public function sendScanResult(array $emails, string $packageName, string $packageId, string $organizationAlias, array $result): void
    {
        foreach ($emails as $email) {
            $this->mailer->send((new TemplatedEmail())
                ->from(Address::fromString($this->sender))
                ->to($email)
                ->subject("Vulnerabilities found in $packageName package")
                ->htmlTemplate('emails/scan-result.html.twig')
                ->context([
                    'packageName' => $packageName,
                    'packageId' => $packageId,
                    'organizationAlias' => $organizationAlias,
                    'result' => $result,
                ])
            );
        }
    }
}
