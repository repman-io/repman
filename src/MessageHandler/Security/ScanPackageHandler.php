<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Security;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Security\PackageScanner;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ScanPackageHandler implements MessageHandlerInterface
{
    public function __construct(private readonly PackageScanner $scanner, private readonly PackageRepository $packages)
    {
    }

    public function __invoke(ScanPackage $message): void
    {
        $package = $this->packages->find(Uuid::fromString($message->id()));
        if (!$package instanceof Package) {
            return;
        }

        if (!$package->isEnabledSecurityScan()) {
            return;
        }

        $this->scanner->scan($package);
    }
}
