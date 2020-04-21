<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Doctrine\DBAL\Connection;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\PdoStore;

class ProxySyncReleasesCommand extends Command
{
    const LOCK_TTL = 900;

    private ProxyRegister $register;
    private Downloader $downloader;
    private AdapterInterface $cache;
    private LockFactory $lockFactory;

    public function __construct(ProxyRegister $register, Downloader $downloader, AdapterInterface $packagistReleasesFeedCache, Connection $connection)
    {
        $this->register = $register;
        $this->downloader = $downloader;
        $this->cache = $packagistReleasesFeedCache;
        $this->lockFactory = new LockFactory(new PdoStore($connection));

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
        $lock = $this
            ->lockFactory
            ->createLock('packagist_releases_feed', self::LOCK_TTL);
        if (!$lock->acquire()) {
            return 0;
        }

        try {
            $feed = $this->loadFeed();
            if (!$this->alreadySynced((string) $feed->channel->pubDate)) {
                $this->syncPackages($feed);
            }
        } finally {
            $lock->release();
        }

        return 0;
    }

    private function syncPackages(\SimpleXMLElement $feed): void
    {
        $proxy = $this
            ->register
            ->getByHost('packagist.org');

        $syncedPackages = [];
        foreach ($proxy->syncedPackages() as $name) {
            $syncedPackages[$name] = true;
        }

        foreach ($feed->channel->item as $item) {
            list($name, $version) = explode(' ', (string) $item->guid);
            if (isset($syncedPackages[$name])) {
                $proxy->downloadByVersion($name, $version);
            }
        }
    }

    private function alreadySynced(string $pubDate): bool
    {
        $lastPubDateCashed = $this->cache->getItem('pub_date');
        if (!$lastPubDateCashed->isHit()) {
            $lastPubDateCashed->set($pubDate);
            $this->cache->save($lastPubDateCashed);

            return false;
        }

        $lastPubDate = $lastPubDateCashed->get();

        return new \DateTimeImmutable($pubDate) <= new \DateTimeImmutable($lastPubDate);
    }

    private function loadFeed(): \SimpleXMLElement
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
