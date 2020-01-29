<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\SendPasswordResetLink;
use Buddy\Repman\Service\Mailer;
use Buddy\Repman\Service\User\ResetPasswordTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class SendPasswordResetLinkHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private Mailer $mailer;
    private ResetPasswordTokenGenerator $generator;

    public function __construct(EntityManagerInterface $em, Mailer $mailer, ResetPasswordTokenGenerator $generator)
    {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->generator = $generator;
    }

    public function __invoke(SendPasswordResetLink $message): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $message->email()]);
        if (!$user instanceof User) {
            return;
        }

        $token = $this->generator->generate();
        $user->setResetPasswordToken($token);
        $this->mailer->sendPasswordResetLink($user->getEmail(), $token, $message->operatingSystem(), $message->browser());
        $this->em->flush();
    }
}
