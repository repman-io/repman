<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Repository\VersionRepository;
use Buddy\Repman\Service\Organization\PackageManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ClearOldPrivateDistsCommand extends Command
{
    private PackageQuery $query;
    private VersionRepository $repository;
    private PackageManager $packageManager;

    public function __construct(PackageQuery $query, VersionRepository $repository, PackageManager $packageManager)
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
        $count = $this->query->getAllSynchronizedCount();

        for ($offset = 0; $offset < $count; $offset += $limit) {
            foreach ($this->query->getAllSynchronized($limit, $offset) as $package) {
                $excludeVersion = $this->query->findLatestNonStableVersion($package->id());
                if ($excludeVersion === null) {
                    continue;
                }

                foreach ($this->query->findNonStableVersions($package->id()) as $version) {
                    if ($excludeVersion->id() !== $version->id()) {
                        $this->repository->remove(Uuid::fromString($version->id()));
                    }

                    $this->packageManager->removeVersionDists(
                        $package->organization(),
                        $package->name(),
                        $version->version(),
                        'zip',
                        $excludeVersion->reference(),
                    );
                }
            }
        }

        return 0;
    }
}
