<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Security\PackageScanner;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScanAllPackagesCommand extends Command
{
    private PackageScanner $scanner;
    private PackageQuery $packageQuery;
    private PackageRepository $packageRepository;

    public function __construct(PackageScanner $scanner, PackageQuery $packageQuery, PackageRepository $packageRepository)
    {
        $this->scanner = $scanner;
        $this->packageQuery = $packageQuery;
        $this->packageRepository = $packageRepository;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('repman:security:scan-all')
            ->setDescription('Scan all synchronized packages')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = $this->packageQuery->getAllSynchronizedCount();
        $limit = 50;
        $offset = 0;

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        for ($offset = 0; $offset <= $count; $offset = ($offset + 1) * $limit) {
            $list = $this->packageQuery->getAllSynchronized($limit, $offset);

            foreach ($list as $item) {
                $this->scanner->scan(
                    $this->packageRepository->getById(Uuid::fromString($item->id()))
                );
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $output->writeln(sprintf("\nSuccessfully scanned %d packages", $count));

        return 0;
    }
}
