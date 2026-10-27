<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Proxy;

use Buddy\Repman\Message\Proxy\RemoveDist;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RemoveDistHandler
{
    private ProxyRegister $register;

    public function __construct(ProxyRegister $register)
    {
        $this->register = $register;
    }

    public function __invoke(RemoveDist $message): void
    {
        $this->register->getByHost($message->proxy())->removeDist($message->packageName());
    }
}
