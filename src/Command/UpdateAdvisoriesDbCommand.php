<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Service\Security\SecurityChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAdvisoriesDbCommand extends Command
{
    private SecurityChecker $checker;

    public function __construct(SecurityChecker $checker)
    {
        parent::__construct();

        $this->checker = $checker;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('repman:security:update-db')
            ->setDescription('Update security advisories database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checker->update();

        return 0;
    }
}
