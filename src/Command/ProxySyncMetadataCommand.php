<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

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

    protected static $defaultName = 'repman:proxy:sync-metadata';

    public function __construct(private readonly ProxyRegister $register, private readonly LockFactory $lockFactory)
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Sync proxy metadata with origins')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lock = $this->lockFactory->createLock(self::LOCK_NAME, self::LOCK_TTL);
        if (!$lock->acquire()) {
            return 0;
        }

        try {
            $this->register->all()->forEach(function (Proxy $proxy) use ($lock): void {
                $proxy->syncMetadata();
                $lock->refresh();
                $proxy->updateLatestProviders();
                $lock->refresh();
            });
        } finally {
            $lock->release();
        }

        return 0;
    }
}
