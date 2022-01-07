<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Security\PackageScanner;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ScanAllPackagesCommand extends Command
{
    protected static $defaultName = 'repman:security:scan-all';

    private PackageScanner $scanner;
    private PackageQuery $packageQuery;
    private PackageRepository $packageRepository;
    private EntityManagerInterface $em;

    public function __construct(PackageScanner $scanner, PackageQuery $packageQuery, PackageRepository $packageRepository, EntityManagerInterface $em)
    {
        $this->scanner = $scanner;
        $this->packageQuery = $packageQuery;
        $this->packageRepository = $packageRepository;
        $this->em = $em;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Scan all synchronized packages')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->packageQuery->getAllSynchronizedCount();
        $limit = 50;

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        for ($offset = 0; $offset < $count; $offset += $limit) {
            foreach ($this->packageQuery->getAllSynchronized($limit, $offset) as $item) {
                $this->scanner->scan(
                    $this->packageRepository->getById(Uuid::fromString($item->id()))
                );
                $progressBar->advance();
            }
            $this->em->clear();
        }

        $progressBar->finish();
        $output->writeln(sprintf("\nSuccessfully scanned %d packages", $count));

        return 0;
    }
}
