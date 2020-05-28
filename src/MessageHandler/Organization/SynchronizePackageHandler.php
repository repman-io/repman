<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\PackageSynchronizer;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final class SynchronizePackageHandler implements MessageHandlerInterface
{
    private PackageSynchronizer $synchronizer;
    private PackageRepository $packages;
    private MessageBusInterface $messageBus;

    public function __construct(PackageSynchronizer $synchronizer, PackageRepository $packages, MessageBusInterface $messageBus)
    {
        $this->synchronizer = $synchronizer;
        $this->packages = $packages;
        $this->messageBus = $messageBus;
    }

    public function __invoke(SynchronizePackage $message): void
    {
        $package = $this->packages->find(Uuid::fromString($message->id()));
        if (!$package instanceof Package) {
            return;
        }

        $this->synchronizer->synchronize($package);

        if ($package->isSynchronizedSuccessfully()) {
            $this->messageBus->dispatch(
                (new Envelope(new ScanPackage($package->id()->toString())))
                    ->with(new DispatchAfterCurrentBusStamp())
            );
        }
    }
}
