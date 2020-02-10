<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\SynchronizePackage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class SynchronizePackageHandler implements MessageHandlerInterface
{
    public function __invoke(SynchronizePackage $message): void
    {
        $message->id();
    }
}
