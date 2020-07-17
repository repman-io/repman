<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Proxy;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;

final class ProxySyncMetadataCommand extends Command
{
    public const LOCK_TTL = 60;
    public const LOCK_NAME = 'proxy_metadata';

    private ProxyRegister $register;
    private Downloader $downloader;
    private LockFactory $lockFactory;

    public function __construct(ProxyRegister $register, Downloader $downloader, LockFactory $lockFactory)
    {
        $this->register = $register;
        $this->downloader = $downloader;
        $this->lockFactory = $lockFactory;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('repman:proxy:sync-metadata')
            ->setDescription('Sync proxy metadata with origins')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = $this->lockFactory->createLock(self::LOCK_NAME, self::LOCK_TTL);
        if (!$lock->acquire()) {
            return 0;
        }

        try {
            $this->register->all()->forEach(function (Proxy $proxy) use ($lock): void {
                $proxy->syncMetadata();
                $lock->refresh();
            });
        } finally {
            $lock->release();
        }

        return 0;
    }
}
