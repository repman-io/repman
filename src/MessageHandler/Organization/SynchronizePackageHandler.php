<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\PackageSynchronizer;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class SynchronizePackageHandler implements MessageHandlerInterface
{
    private PackageSynchronizer $synchronizer;
    private PackageRepository $packages;

    public function __construct(PackageSynchronizer $synchronizer, PackageRepository $packages)
    {
        $this->synchronizer = $synchronizer;
        $this->packages = $packages;
    }

    public function __invoke(SynchronizePackage $message): void
    {
        $package = $this->packages->getById(Uuid::fromString($message->id()));
        $this->synchronizer->synchronize($package);
    }
}
