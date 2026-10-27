<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Service\Security\SecurityChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'repman:security:update-db', description: 'Update security advisories database, scan all packages if updated.')]
final class UpdateAdvisoriesDbCommand extends Command
{
    private SecurityChecker $checker;
    private ScanAllPackagesCommand $scanCommand;

    public function __construct(SecurityChecker $checker, ScanAllPackagesCommand $scanCommand)
    {
        parent::__construct();

        $this->checker = $checker;
        $this->scanCommand = $scanCommand;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->checker->update()) {
            $this->scanCommand->execute(new ArrayInput([]), new NullOutput());
        }

        return Command::SUCCESS;
    }
}
