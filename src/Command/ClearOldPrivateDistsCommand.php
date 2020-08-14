<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Query\Admin\VersionQuery;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Repository\VersionRepository;
use Buddy\Repman\Service\Organization\PackageManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ClearOldPrivateDistsCommand extends Command
{
    private VersionQuery $versionQuery;
    private PackageQuery $packageQuery;
    private VersionRepository $repository;
    private PackageManager $packageManager;

    public function __construct(VersionQuery $versionQuery, PackageQuery $packageQuery, VersionRepository $repository, PackageManager $packageManager)
    {
        $this->versionQuery = $versionQuery;
        $this->packageQuery = $packageQuery;
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
        $count = $this->packageQuery->getAllSynchronizedCount();

        for ($offset = 0; $offset < $count; $offset += $limit) {
            foreach ($this->packageQuery->getAllSynchronized($limit, $offset) as $package) {
                foreach ($this->versionQuery->findDevVersions($package->id()) as $version) {
                    $this->repository->remove(Uuid::fromString($version->id()));
                    $this->packageManager->removeVersionDist(
                        $package->organization(),
                        $package->name(),
                        $version->version(),
                        $version->reference(),
                        'zip'
                    );
                }
            }
        }

        return 0;
    }
}
