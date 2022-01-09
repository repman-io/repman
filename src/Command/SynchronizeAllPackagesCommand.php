<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Repository\OrganizationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class SynchronizeAllPackagesCommand extends Command
{
    protected static $defaultName = 'repman:package:synchronize-all';

    private MessageBusInterface $bus;

    private OrganizationRepository $organizations;

    public function __construct(MessageBusInterface $bus, OrganizationRepository $organizations)
    {
        $this->bus = $bus;
        $this->organizations = $organizations;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Synchronize all packages')
            ->addArgument('organization', InputArgument::OPTIONAL, 'Organization alias')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $organizations = $this->getOrganizationsToSync($input->getArgument('organization'));
        $output->writeln(sprintf('Synchronizing packages of %d organizations.', count($organizations)));

        foreach ($organizations as $organization) {
            $output->writeln(sprintf('Synchronizing packages for %s.', $organization->name()));

            foreach ($organization->synchronizedPackages() as $package) {
                $output->writeln(sprintf('- %s.', $package->name()));
                $this->bus->dispatch(new SynchronizePackage($package->id()->toString()));
            }
        }

        return 0;
    }

    /**
     * @return Organization[]
     */
    protected function getOrganizationsToSync(?string $organizationAlias): array
    {
        if ($organizationAlias === null) {
            return $this->organizations->findAll();
        }

        $organization = $this->organizations->findOneBy(['alias' => $organizationAlias]);

        if (!$organization instanceof Organization) {
            throw new \InvalidArgumentException(sprintf('Organization with alias %s not found.', $organizationAlias));
        }

        return [$organization];
    }
}
