<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\ChangeAlias;
use Buddy\Repman\Repository\OrganizationRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ChangeAliasHandler implements MessageHandlerInterface
{
    public function __construct(private readonly OrganizationRepository $repositories)
    {
    }

    public function __invoke(ChangeAlias $message): void
    {
        $this->repositories
            ->getById(Uuid::fromString($message->organizationId()))
            ->changeAlias($message->alias())
        ;
    }
}
