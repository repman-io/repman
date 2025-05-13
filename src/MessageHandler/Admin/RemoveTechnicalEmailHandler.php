<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Admin;

use Buddy\Repman\Message\Admin\RemoveTechnicalEmail;
use Buddy\Repman\Service\Telemetry;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveTechnicalEmailHandler implements MessageHandlerInterface
{
    public function __construct(private readonly Telemetry $telemetry)
    {
    }

    public function __invoke(RemoveTechnicalEmail $message): void
    {
        $this->telemetry->removeTechnicalEmail($message->email());
    }
}
