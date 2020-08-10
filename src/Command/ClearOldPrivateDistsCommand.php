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
            ->setDescription('Clear private distributions files older than 30 days');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $daysOld = 30;
        $limit = 100;
        $count = $this->query->oldDistsCount($daysOld);
        for ($offset = 0; $offset < $count; $offset += $limit) {
            $versions = $this->query->findOldDists($daysOld, $limit, $offset);
            foreach ($versions as $version) {
                $this->repository->remove(Uuid::fromString($version['id']));
                $this->packageManager->removeVersionDist(
                    $version['organization'],
                    $version['package_name'],
                    $version['version'],
                    $version['reference'],
                    'zip'
                );
            }
        }

        return 0;
    }
}
