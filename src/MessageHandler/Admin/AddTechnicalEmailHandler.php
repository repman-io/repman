<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Admin;

use Buddy\Repman\Message\Admin\AddTechnicalEmail;
use Buddy\Repman\Service\Telemetry;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AddTechnicalEmailHandler implements MessageHandlerInterface
{
    public function __construct(private readonly Telemetry $telemetry)
    {
    }

    public function __invoke(AddTechnicalEmail $message): void
    {
        $this->telemetry->addTechnicalEmail($message->email());
    }
}
