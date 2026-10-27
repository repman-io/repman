<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\User as SecurityUser;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

#[AsMessageHandler]
final class CreateOAuthUserHandler
{
    private UserRepository $users;
    private PasswordHasherFactoryInterface $hasherFactory;

    public function __construct(UserRepository $users, PasswordHasherFactoryInterface $hasherFactory)
    {
        $this->users = $users;
        $this->hasherFactory = $hasherFactory;
    }

    public function __invoke(CreateOAuthUser $message): void
    {
        $user = new User(
            Uuid::uuid4(),
            $message->email(),
            $confirmToken = Uuid::uuid4()->toString(),
            ['ROLE_USER', 'ROLE_OAUTH_USER']
        );
        $user->setPassword($this->hasherFactory->getPasswordHasher(SecurityUser::class)->hash(Uuid::uuid4()->toString()));
        $user->confirmEmail($confirmToken);

        $this->users->add($user);
    }
}
