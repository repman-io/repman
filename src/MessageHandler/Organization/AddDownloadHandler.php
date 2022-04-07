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
    private PackageRepository $packages;

    public function __construct(PackageRepository $packages)
    {
        $this->packages = $packages;
    }

    public function __invoke(AddDownload $message): void
    {
        $this->packages->addDownload(new Download(
            Uuid::uuid4(),
            Uuid::fromString($message->packageId()),
            $message->date(),
            $message->version(),
            $message->ip(),
            $message->userAgent() !== null ? (string) substr($message->userAgent(), 0, 255) : null
        ));
    }
}
