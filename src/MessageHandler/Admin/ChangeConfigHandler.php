<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Admin;

use Buddy\Repman\Message\Admin\ChangeConfig;
use Buddy\Repman\Repository\ConfigRepository;
use Buddy\Repman\Service\Config;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ChangeConfigHandler implements MessageHandlerInterface
{
    private ConfigRepository $configRepository;
    private Config $config;

    public function __construct(ConfigRepository $configRepository, Config $config)
    {
        $this->configRepository = $configRepository;
        $this->config = $config;
    }

    public function __invoke(ChangeConfig $message): void
    {
        $this->configRepository->change($message->values());
        $this->config->invalidate();
    }
}
