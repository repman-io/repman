<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\User as SecurityUser;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

final class CreateOAuthUserHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private EncoderFactoryInterface $encoderFactory;

    public function __construct(UserRepository $users, EncoderFactoryInterface $encoderFactory)
    {
        $this->users = $users;
        $this->encoderFactory = $encoderFactory;
    }

    public function __invoke(CreateOAuthUser $message): void
    {
        $user = new User(
            Uuid::uuid4(),
            $message->email(),
            $confirmToken = Uuid::uuid4()->toString(),
            ['ROLE_USER', 'ROLE_OAUTH_USER']
        );
        $user->setPassword($this->encoderFactory->getEncoder(SecurityUser::class)->encodePassword(Uuid::uuid4()->toString(), null));
        $user->confirmEmail($confirmToken);

        $this->users->add($user);
    }
}
