<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Repository\PackageRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class SynchronizePackageCommand extends Command
{
    protected static $defaultName = 'repman:package:synchronize';

    private MessageBusInterface $bus;
    private PackageRepository $packages;

    public function __construct(MessageBusInterface $bus, PackageRepository $packages)
    {
        $this->bus = $bus;
        $this->packages = $packages;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Synchronize given package')
            ->addArgument('packageId', InputArgument::REQUIRED, 'package UUID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $packageId */
        $packageId = $input->getArgument('packageId');
        if (!$this->packages->find(Uuid::fromString($packageId)) instanceof Package) {
            $output->writeln('Package not found');

            return 1;
        }

        $this->bus->dispatch(new SynchronizePackage($packageId));

        return 0;
    }
}
