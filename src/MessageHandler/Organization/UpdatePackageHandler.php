<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\UpdatePackage;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Repository\PackageRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class UpdatePackageHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;
    private PackageRepository $packages;

    public function __construct(OrganizationRepository $organizations, PackageRepository $packages)
    {
        $this->organizations = $organizations;
        $this->packages = $packages;
    }

    public function __invoke(UpdatePackage $message): void
    {
        $organizationId = $message->organizationId();
        $packageId = $message->id();

        // TODO: dispatch repo fetch, compare versions, update if necessary
        $this->packages->getById(
            Uuid::fromString($packageId),
            Uuid::fromString($organizationId)
        );
    }
}
