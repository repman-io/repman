<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Message\User\RegenerateApiToken;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RegenerateApiTokenHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private TokenGenerator $tokenGenerator;

    public function __construct(UserRepository $users, TokenGenerator $tokenGenerator)
    {
        $this->users = $users;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function __invoke(RegenerateApiToken $message): void
    {
        $this->users
            ->getById(Uuid::fromString($message->userId()))
            ->regenerateApiToken($message->token(), $this->tokenGenerator->generate())
        ;
    }
}
