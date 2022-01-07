<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Service\Security\SecurityChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateAdvisoriesDbCommand extends Command
{
    protected static $defaultName = 'repman:security:update-db';

    private SecurityChecker $checker;
    private ScanAllPackagesCommand $scanCommand;

    public function __construct(SecurityChecker $checker, ScanAllPackagesCommand $scanCommand)
    {
        parent::__construct();

        $this->checker = $checker;
        $this->scanCommand = $scanCommand;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Update security advisories database, scan all packages if updated.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->checker->update()) {
            $this->scanCommand->execute(new ArrayInput([]), new NullOutput());
        }

        return 0;
    }
}
