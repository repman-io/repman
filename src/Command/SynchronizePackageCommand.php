<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Repository\PackageRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'repman:package:synchronize', description: 'Synchronize given package')]
final class SynchronizePackageCommand extends Command
{
    private MessageBusInterface $bus;
    private PackageRepository $packages;

    public function __construct(MessageBusInterface $bus, PackageRepository $packages)
    {
        $this->bus = $bus;
        $this->packages = $packages;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('packageId', InputArgument::REQUIRED, 'package UUID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $packageId */
        $packageId = $input->getArgument('packageId');
        if (!$this->packages->find(Uuid::fromString($packageId)) instanceof Package) {
            $output->writeln('Package not found');

            return Command::FAILURE;
        }

        $this->bus->dispatch(new SynchronizePackage($packageId));

        return Command::SUCCESS;
    }
}
