<?php

declare(strict_types=1);

namespace Buddy\Repman\Service\Doctrine;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Security\ScanPackage;
use Symfony\Component\Messenger\MessageBusInterface;

final class PackageSynchronizedListener
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function postUpdate(Package $package): void
    {
        if ($package->isSynchronizedSuccessfully()) {
            $this->messageBus->dispatch(new ScanPackage($package->id()->toString()));
        }
    }
}
