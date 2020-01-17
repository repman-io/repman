<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Proxy;

use Buddy\Repman\Message\Proxy\RemoveDist;
use Buddy\Repman\Service\Dist\Storage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveDistHandler implements MessageHandlerInterface
{
    private Storage $distStorage;

    public function __construct(Storage $distStorage)
    {
        $this->distStorage = $distStorage;
    }

    public function __invoke(RemoveDist $message): void
    {
        $this->distStorage->remove($message->packageName());
    }
}
