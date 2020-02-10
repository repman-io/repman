<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\RemovePackage;
use Buddy\Repman\Repository\OrganizationRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemovePackageHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;

    public function __construct(OrganizationRepository $organizations)
    {
        $this->organizations = $organizations;
    }

    public function __invoke(RemovePackage $message): void
    {
        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->removePackage(Uuid::fromString($message->id()))
        ;
    }
}
