<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\ChangeAnonymousAccess;
use Buddy\Repman\Repository\OrganizationRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ChangeAnonymousAccessHandler implements MessageHandlerInterface
{
    private OrganizationRepository $repositories;

    public function __construct(OrganizationRepository $repositories)
    {
        $this->repositories = $repositories;
    }

    public function __invoke(ChangeAnonymousAccess $message): void
    {
        $this->repositories
            ->getById(Uuid::fromString($message->organizationId()))
            ->changeAnonymousAccess($message->hasAnonymousAccess())
        ;
    }
}
