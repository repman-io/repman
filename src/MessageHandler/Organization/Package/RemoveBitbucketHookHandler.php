<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\RemoveBitbucketHook;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RemoveBitbucketHookHandler extends AbstractHookHandler
{
    public function __invoke(RemoveBitbucketHook $message): void
    {
        $package = $this->packages->getById(Uuid::fromString($message->packageId()));
        $this->integrations->bitbucketApi()->removeHook(
            $package->oauthToken()->accessToken($this->tokenRefresher),
            $package->metadata(Metadata::BITBUCKET_REPO_NAME),
            $this->router->generate('package_webhook', ['package' => $package->id()->toString()], UrlGeneratorInterface::ABSOLUTE_URL)
        );
        $package->webhookWasRemoved();
    }
}
