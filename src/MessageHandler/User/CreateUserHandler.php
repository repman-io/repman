<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\CreateUser;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class CreateUserHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private UserPasswordEncoderInterface $encoder;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {
        $this->em = $em;
        $this->encoder = $encoder;
    }

    public function __invoke(CreateUser $message): void
    {
        $user = new User(
            Uuid::fromString($message->id()),
            $message->email(),
            $message->confirmToken(),
            $message->roles()
        );
        $user->setPassword($this->encoder->encodePassword($user, $message->plainPassword()));

        $this->em->persist($user);
        $this->em->flush();
    }
}
