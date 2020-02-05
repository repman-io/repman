<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\ConfirmEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ConfirmEmailHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(ConfirmEmail $message): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['emailConfirmToken' => $message->token()]);
        if (!$user instanceof User) {
            throw new \RuntimeException(sprintf('User with confirm e-mail token %s not found.', $message->token()));
        }

        $user->confirmEmail($message->token());

        $this->em->flush();
    }
}
