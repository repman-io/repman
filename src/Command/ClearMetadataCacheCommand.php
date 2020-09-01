<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class ClearMetadataCacheCommand extends Command
{
    protected static $defaultName = 'repman:metadata:clear-cache';

    private string $distsDir;

    public function __construct(string $distsDir)
    {
        $this->distsDir = $distsDir;
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Clear packages metadata cache (json files)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = Finder::create()->files()->name('*.json')->ignoreVCS(true)->in($this->distsDir);
        $count = $files->count();

        foreach ($files as $file) {
            /* @var SplFileInfo $file */
            @unlink($file->getPathname());
        }

        $count > 0 ? $output->writeln(sprintf('Deleted %s file(s).', $count)) : $output->writeln('No metadata files found.');

        return 0;
    }
}
