<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User\ApiToken;
use Buddy\Repman\Message\User\GenerateApiToken;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class GenerateApiTokenHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private TokenGenerator $tokenGenerator;

    public function __construct(UserRepository $users, TokenGenerator $tokenGenerator)
    {
        $this->users = $users;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function __invoke(GenerateApiToken $message): void
    {
        $this->users
            ->getById(Uuid::fromString($message->userId()))
            ->addApiToken(new ApiToken(
                $this->tokenGenerator->generate(),
                $message->name()
            ))
        ;
    }
}
