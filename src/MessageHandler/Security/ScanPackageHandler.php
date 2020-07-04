<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Security;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Security\PackageScanner;
use Buddy\Repman\Service\Security\SecurityChecker;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ScanPackageHandler implements MessageHandlerInterface
{
    private PackageScanner $scanner;
    private PackageRepository $packages;
    private SecurityChecker $checker;

    public function __construct(PackageScanner $scanner, PackageRepository $packages, SecurityChecker $checker)
    {
        $this->scanner = $scanner;
        $this->packages = $packages;
        $this->checker = $checker;
    }

    public function __invoke(ScanPackage $message): void
    {
        $package = $this->packages->find(Uuid::fromString($message->id()));
        if (!$package instanceof Package) {
            return;
        }

        $this->checker->update();
        $this->scanner->scan($package);
    }
}
