<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Service\Dist\FilePatternFilterIterator;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'repman:metadata:clear-cache', description: 'Clear packages metadata cache (json files)')]
final class ClearMetadataCacheCommand extends Command
{
    private FilesystemOperator $repoFilesystem;

    /** @var array<string> */
    private const VCS_PATTERNS = [
        '.svn', '_svn', 'CVS', '_darcs', '.arch-params', '.monotone', '.bzr', '.git', '.hg',
    ];

    public function __construct(FilesystemOperator $repoFilesystem)
    {
        $this->repoFilesystem = $repoFilesystem;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->repoFilesystem->listContents('/', true)
            ->filter(fn (StorageAttributes $file) => $file->isFile())
            ->map(fn (StorageAttributes $file) => ['path' => $file->path()])
            ->toArray();

        $iterator = new FilePatternFilterIterator(
            new \ArrayIterator($files),
            self::VCS_PATTERNS,
            '/.*json$/'
        );

        $count = 0;
        foreach ($iterator as $file) {
            /* @var array<string, string> $file */
            $this->repoFilesystem->delete($file['path']);
            ++$count;
        }

        $count > 0 ?
            $output->writeln(\sprintf('Deleted %s file(s).', $count))
            : $output->writeln('No metadata files found.');

        return Command::SUCCESS;
    }
}
