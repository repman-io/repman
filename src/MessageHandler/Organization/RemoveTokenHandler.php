<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\RemoveToken;
use Buddy\Repman\Repository\OrganizationRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveTokenHandler implements MessageHandlerInterface
{
    public function __construct(private readonly OrganizationRepository $organizations)
    {
    }

    public function __invoke(RemoveToken $message): void
    {
        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->removeToken($message->token())
        ;
    }
}
