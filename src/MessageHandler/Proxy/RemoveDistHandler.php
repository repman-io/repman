<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Proxy;

use Buddy\Repman\Message\Proxy\RemoveDist;
use Buddy\Repman\Service\Proxy\PackageManager;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveDistHandler implements MessageHandlerInterface
{
    private PackageManager $packageManager;

    public function __construct(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    public function __invoke(RemoveDist $message): void
    {
        $this->packageManager->remove($message->proxy(), $message->packageName());
    }
}
