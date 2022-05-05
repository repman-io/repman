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
    private PackageScanner $scanner;
    private PackageRepository $packages;

    public function __construct(PackageScanner $scanner, PackageRepository $packages)
    {
        $this->scanner = $scanner;
        $this->packages = $packages;
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
