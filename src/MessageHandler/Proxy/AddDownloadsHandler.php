<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Proxy;

use Buddy\Repman\Message\Proxy\AddDownloads;
use Buddy\Repman\Message\Proxy\AddDownloads\Package as MessagePackage;
use Buddy\Repman\Service\Proxy\Downloads;
use Buddy\Repman\Service\Proxy\Downloads\Package;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AddDownloadsHandler implements MessageHandlerInterface
{
    private Downloads $downloads;

    public function __construct(Downloads $downloads)
    {
        $this->downloads = $downloads;
    }

    public function __invoke(AddDownloads $message): void
    {
        $this->downloads->save(
            array_map(fn(MessagePackage $package): Package => new Package($package->name(), $package->version()), $message->packages()),
            $message->date(),
            $message->ip(),
            $message->userAgent()
        );
    }
}
