<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Proxy;

use Buddy\Repman\Message\Proxy\RemoveDist;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveDistHandler implements MessageHandlerInterface
{
    public function __construct(private readonly ProxyRegister $register)
    {
    }

    public function __invoke(RemoveDist $message): void
    {
        $this->register->getByHost($message->proxy())->removeDist($message->packageName());
    }
}
