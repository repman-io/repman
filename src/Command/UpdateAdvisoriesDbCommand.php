<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Security\PackageScanner;
use Buddy\Repman\Service\Security\SecurityChecker;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAdvisoriesDbCommand extends Command
{
    private SecurityChecker $checker;
    private PackageScanner $scanner;
    private PackageQuery $packageQuery;
    private PackageRepository $packageRepository;

    public function __construct(SecurityChecker $checker, PackageScanner $scanner, PackageQuery $packageQuery, PackageRepository $packageRepository)
    {
        parent::__construct();

        $this->checker = $checker;
        $this->scanner = $scanner;
        $this->packageQuery = $packageQuery;
        $this->packageRepository = $packageRepository;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('repman:security:update-db')
            ->setDescription('Update security advisories database, scan all packages if updated.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checker->update();

        if ($this->checker->hasDbBeenUpdated()) {
            $count = $this->packageQuery->getAllSynchronizedCount();
            $limit = 50;
            $offset = 0;

            for ($offset = 0; $offset <= $count; $offset = ($offset + 1) * $limit) {
                $list = $this->packageQuery->getAllSynchronized($limit, $offset);
                foreach ($list as $item) {
                    $this->scanner->scan(
                        $this->packageRepository->getById(Uuid::fromString($item->id()))
                    );
                }
            }
        }

        return 0;
    }
}
