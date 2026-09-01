<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Entity\Organization;
use Buddy\Repman\Message\Organization\AddPackage;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Repository\OrganizationRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

final class AddPackageCommand extends Command
{
    protected static $defaultName = 'repman:package:add';

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
            ->setDescription('Add an artifact package to an organization')
            ->addArgument('organization', InputArgument::REQUIRED, 'Organization alias')
            ->addArgument('type', InputArgument::REQUIRED, 'Type of the project. Just artifact available for now.')
            ->addArgument('url', InputArgument::REQUIRED, 'Artifact path')
            ->addOption('keep-last-releases', 'k', InputOption::VALUE_OPTIONAL, 'Artifact path', '0')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getArgument('type') !== 'artifact') {
            $io->error('Just artifact available for now.');

            return 1;
        }

        try {
            $organization = $this->getOrganizationFromAlias($input->getArgument('organization'));
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $this->bus->dispatch(new AddPackage(
            $id = Uuid::uuid4()->toString(),
            $organization->id()->toString(),
            $input->getArgument('url'),
            'artifact',
            [],
            intval($input->getOption('keep-last-releases'))
        ));
        $this->bus->dispatch(new SynchronizePackage($id));

        $output->writeln('Package has been added and will be synchronized in the background');

        return 0;
    }

    protected function getOrganizationFromAlias(string $organizationAlias): Organization
    {
        $organization = $this->organizations->findOneBy(['alias' => $organizationAlias]);

        if (!$organization instanceof Organization) {
            throw new \InvalidArgumentException(sprintf('Organization with alias %s not found.', $organizationAlias));
        }

        return $organization;
    }
}
