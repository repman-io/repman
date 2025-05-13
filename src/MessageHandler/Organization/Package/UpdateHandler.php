<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Organization\Package\Update;
use Buddy\Repman\Repository\PackageRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class UpdateHandler implements MessageHandlerInterface
{
    public function __construct(private readonly PackageRepository $packages)
    {
    }

    public function __invoke(Update $message): void
    {
        $package = $this->packages->find(Uuid::fromString($message->packageId()));
        if (!$package instanceof Package) {
            return;
        }

        $package->update($message->url(), $message->keepLastReleases(), $message->isEnabledSecurityScan());
    }
}
