<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Service\Config;
use Buddy\Repman\Service\Telemetry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SendTelemetryCommand extends Command
{
    private Config $config;
    private Telemetry $telemetry;

    public function __construct(Config $config, Telemetry $telemetry)
    {
        $this->config = $config;
        $this->telemetry = $telemetry;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('repman:telemetry:send')
            ->setDescription('Send telemetry data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->telemetry->isInstanceIdPresent()) {
            return 0;
        }

        if (!$this->config->telemetryEnabled()) {
            return 0;
        }

        $this->telemetry
            ->collectAndSend((new \DateTimeImmutable())->modify('-1 day'));

        return 0;
    }
}
