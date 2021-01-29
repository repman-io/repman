<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AddBitbucketHookHandler extends AbstractHookHandler
{
    public function __invoke(AddBitbucketHook $message): void
    {
        $package = $this->packages->find(Uuid::fromString($message->packageId()));
        if (!$package instanceof Package) {
            return;
        }

        try {
            $this->integrations->bitbucketApi()->addHook(
                $package->oauthToken()->accessToken($this->tokenRefresher),
                $package->metadata(Metadata::BITBUCKET_REPO_NAME),
                $this->router->generate('package_webhook', ['package' => $package->id()->toString()], UrlGeneratorInterface::ABSOLUTE_URL)
            );
            $package->webhookWasCreated();
        } catch (\Throwable $exception) {
            $package->webhookWasNotCreated($exception->getMessage());
        }
    }
}
