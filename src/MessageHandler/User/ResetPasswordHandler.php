<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\ResetPassword;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class ResetPasswordHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private UserPasswordEncoderInterface $encoder;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {
        $this->em = $em;
        $this->encoder = $encoder;
    }

    public function __invoke(ResetPassword $message): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['resetPasswordToken' => $message->token()]);
        if (!$user instanceof User) {
            return;
        }

        $user->resetPassword($message->token(), $this->encoder->encodePassword($user, $message->password()));
        $this->em->flush();
    }
}
