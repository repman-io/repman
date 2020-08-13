<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Query\Admin\VersionQuery;
use Buddy\Repman\Repository\VersionRepository;
use Buddy\Repman\Service\Organization\PackageManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ClearOldPrivateDistsCommand extends Command
{
    private VersionQuery $query;
    private VersionRepository $repository;
    private PackageManager $packageManager;

    public function __construct(VersionQuery $query, VersionRepository $repository, PackageManager $packageManager)
    {
        $this->query = $query;
        $this->repository = $repository;
        $this->packageManager = $packageManager;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('repman:package:clear-old-dists')
            ->setDescription('Clear old private dev distributions files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = 100;
        $count = $this->query->oldDistsCount();

        for ($offset = 0; $offset < $count; $offset += $limit) {
            foreach ($this->query->findPackagesWithDevVersions($limit, $offset) as $package) {
                foreach ($this->query->findPackagesDevVersions($package['id']) as $version) {
                    $this->repository->remove(Uuid::fromString($version['id']));
                    $this->packageManager->removeVersionDist(
                        $package['organization'],
                        $package['name'],
                        $version['version'],
                        $version['reference'],
                        'zip'
                    );
                }
            }
        }

        return 0;
    }
}
