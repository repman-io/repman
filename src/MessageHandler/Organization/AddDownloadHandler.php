<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization\Package\Download;
use Buddy\Repman\Message\Organization\AddDownload;
use Buddy\Repman\Repository\PackageRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AddDownloadHandler implements MessageHandlerInterface
{
    public function __construct(private readonly PackageRepository $packages)
    {
    }

    public function __invoke(AddDownload $message): void
    {
        $this->packages->addDownload(new Download(
            Uuid::uuid4(),
            Uuid::fromString($message->packageId()),
            $message->date(),
            $message->version(),
            $message->ip(),
            $message->userAgent() !== null ? substr($message->userAgent(), 0, 255) : null
        ));
    }
}
