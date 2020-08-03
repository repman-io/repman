<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Admin;

use Buddy\Repman\Message\Admin\AddTechnicalEmail;
use Buddy\Repman\Message\Admin\ChangeConfig;
use Buddy\Repman\Message\Admin\RemoveTechnicalEmail;
use Buddy\Repman\Repository\ConfigRepository;
use Buddy\Repman\Service\Config;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final class ChangeConfigHandler implements MessageHandlerInterface
{
    private ConfigRepository $configRepository;
    private Config $config;
    private MessageBusInterface $messageBus;

    public function __construct(ConfigRepository $configRepository, Config $config, MessageBusInterface $messageBus)
    {
        $this->configRepository = $configRepository;
        $this->config = $config;
        $this->messageBus = $messageBus;
    }

    public function __invoke(ChangeConfig $message): void
    {
        if (array_key_exists(Config::TECHNICAL_EMAIL, $message->values())) {
            $this->handleTechnicalEmail((string) $message->values()[Config::TECHNICAL_EMAIL]);
        }

        $this->configRepository->change($message->values());
        $this->config->invalidate();
    }

    private function handleTechnicalEmail(string $newTechnicalEmail): void
    {
        $oldTechnicalEmail = (string) $this->config->get(Config::TECHNICAL_EMAIL);

        if ($newTechnicalEmail === $oldTechnicalEmail) {
            return;
        }

        $newTechnicalEmail === ''
            ? $this->dispatchAfterCurrentBusStamp(new RemoveTechnicalEmail($oldTechnicalEmail))
            : $this->dispatchAfterCurrentBusStamp(new AddTechnicalEmail($newTechnicalEmail));
    }

    /**
     * @param object $message
     */
    private function dispatchAfterCurrentBusStamp($message): void
    {
        $this->messageBus->dispatch(
            (new Envelope($message))->with(new DispatchAfterCurrentBusStamp())
        );
    }
}
