<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\Security\PackageScanner;
use Buddy\Repman\Service\Security\SecurityChecker;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ScanAllPackagesCommand extends Command
{
    private SecurityChecker $checker;
    private PackageScanner $scanner;
    private PackageQuery $packageQuery;
    private PackageRepository $packageRepository;
    private EntityManagerInterface $em;

    public function __construct(
        SecurityChecker $checker,
        PackageScanner $scanner,
        PackageQuery $packageQuery,
        PackageRepository $packageRepository,
        EntityManagerInterface $em
    ) {
        $this->checker = $checker;
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
            ->setName('repman:security:scan-all')
            ->setDescription('Scan all synchronized packages')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Updating advisories database...");
        $this->checker->update();

        $count = $this->packageQuery->getAllSynchronizedCount();
        $limit = 50;

        $output->writeln("Scanning packages...");

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        for ($offset = 0; $offset < $count; $offset += $limit) {
            foreach ($this->packageQuery->getAllSynchronized($limit, $offset) as $item) {
                $package = $this->packageRepository->getById(Uuid::fromString($item->id()));
                $this->scanner->scan($package);

                $progressBar->advance();
            }
            $this->em->clear();
        }

        $progressBar->finish();
        $output->writeln(sprintf("\nSuccessfully scanned %d packages", $count));

        return 0;
    }
}
