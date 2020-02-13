<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\RemoveOrganization;
use Buddy\Repman\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveOrganizationHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private OrganizationRepository $organizations;

    public function __construct(OrganizationRepository $organizations, EntityManagerInterface $em)
    {
        $this->organizations = $organizations;
        $this->em = $em;
    }

    public function __invoke(RemoveOrganization $message): void
    {
        $this
            ->organizations
            ->remove(Uuid::fromString($message->id()));
    }
}
