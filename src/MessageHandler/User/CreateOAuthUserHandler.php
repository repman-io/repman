<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class CreateOAuthUserHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private UserPasswordEncoderInterface $encoder;

    public function __construct(UserRepository $users, UserPasswordEncoderInterface $encoder)
    {
        $this->users = $users;
        $this->encoder = $encoder;
    }

    public function __invoke(CreateOAuthUser $message): void
    {
        $user = new User(
            Uuid::uuid4(),
            $message->email(),
            $confirmToken = Uuid::uuid4()->toString(),
            ['ROLE_USER', 'ROLE_OAUTH_USER']
        );
        $user->setPassword($this->encoder->encodePassword($user, Uuid::uuid4()->toString()));
        $user->confirmEmail($confirmToken);

        $this->users->add($user);
    }
}
