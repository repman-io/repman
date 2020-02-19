<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\RemoveOrganization;
use Buddy\Repman\Repository\OrganizationRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveOrganizationHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;

    public function __construct(OrganizationRepository $organizations)
    {
        $this->organizations = $organizations;
    }

    public function __invoke(RemoveOrganization $message): void
    {
        $this
            ->organizations
            ->remove(Uuid::fromString($message->id()));
    }
}
