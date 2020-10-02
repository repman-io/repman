<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Repository\OrganizationRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AddPackageHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;

    public function __construct(OrganizationRepository $organizations)
    {
        $this->organizations = $organizations;
    }

    public function __invoke(AddPackage $message): void
    {
        $this->organizations
            ->getById(Uuid::fromString($message->organizationId()))
            ->addPackage(
                new Package(
                    Uuid::fromString($message->id()),
                    $message->type(),
                    $message->url(),
                    $message->metadata(),
                    $message->keepLastReleases()
                )
            )
        ;
    }
}
