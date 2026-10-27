<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Security;

use Buddy\Repman\Message\Security\SendScanResult;
use Buddy\Repman\Service\Mailer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendScanResultHandler
{
    private Mailer $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function __invoke(SendScanResult $message): void
    {
        $this->mailer->sendScanResult(
            $message->emails(),
            $message->packageName(),
            $message->packageId(),
            $message->organizationAlias(),
            $message->result()
        );
    }
}
