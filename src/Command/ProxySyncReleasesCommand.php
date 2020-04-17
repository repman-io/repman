<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProxySyncReleasesCommand extends Command
{
    private ProxyRegister $register;
    private Downloader $downloader;

    public function __construct(ProxyRegister $register, Downloader $downloader)
    {
        $this->register = $register;
        $this->downloader = $downloader;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('repman:proxy:sync-releases')
            ->setDescription('Sync proxy releases with packagist.org')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = time();
        $proxy = $this
            ->register
            ->getByHost('packagist.org');

        foreach ($this->feed()->channel->item as $item) {
            list($name, $version) = explode(' ', (string) $item->guid);
            $this->syncPackages($proxy, $name, $version);
        }

        $output->writeln(sprintf('Done in %ds', time() - $from));

        return 0;
    }

    private function syncPackages(Proxy $proxy, string $name, string $version): void
    {
        foreach ($proxy->syncedPackages() as $package) {
            if ($package !== $name) {
                continue;
            }

            $proxy->downloadByVersion($name, $version);
        }
    }

    private function feed(): \SimpleXMLElement
    {
        $string = $this
            ->downloader
            ->getContents('https://packagist.org/feeds/releases.rss')
            ->getOrElse('');

        $xml = @simplexml_load_string($string);
        if ($xml === false) {
            throw new \RunTimeException('Unable to parse RSS feed');
        }

        return $xml;
    }
}
