<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Used to pre-download the dists of new releases listed in packagist.org's release feed. Packagist
 * refuses Repman's requests to packagist.org (the feed included), asking mirrors to use
 * repo.packagist.org, which has no such feed. Dists are downloaded on first use instead, and
 * repman:proxy:sync-metadata keeps metadata current.
 *
 * Kept so existing crontabs keep working; it will be removed in a future release.
 */
#[AsCommand(name: 'repman:proxy:sync-releases', description: 'Deprecated: does nothing, see repman:proxy:sync-metadata')]
final class ProxySyncReleasesCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<comment>repman:proxy:sync-releases is deprecated and does nothing: packagist.org no longer serves its release feed to Repman. Remove it from your crontab; repman:proxy:sync-metadata keeps the proxy current.</comment>');

        return Command::SUCCESS;
    }
}
