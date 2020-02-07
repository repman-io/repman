<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\RemoveToken;
use Buddy\Repman\Repository\OrganizationRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveTokenHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;

    public function __construct(OrganizationRepository $organizations)
    {
        $this->organizations = $organizations;
    }

    public function __invoke(RemoveToken $message): void
    {
        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->removeToken($message->token())
        ;
    }
}
