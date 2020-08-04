<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Admin;

use Buddy\Repman\Message\Admin\RemoveTechnicalEmail;
use Buddy\Repman\Service\Telemetry;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RemoveTechnicalEmailHandler implements MessageHandlerInterface
{
    private Telemetry $telemetry;

    public function __construct(Telemetry $telemetry)
    {
        $this->telemetry = $telemetry;
    }

    public function __invoke(RemoveTechnicalEmail $message): void
    {
        $this->telemetry->removeTechnicalEmail($message->email());
    }
}
