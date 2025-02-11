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
    public function __construct(private readonly UserRepository $users, private readonly TokenGenerator $tokenGenerator)
    {
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
