<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\RemoveGitHubHook;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RemoveGitHubHookHandler extends AbstractHookHandler
{
    public function __invoke(RemoveGitHubHook $message): void
    {
        $package = $this->packages->getById(Uuid::fromString($message->packageId()));

        $this->integrations->gitHubApi()->removeHook(
            $package->oauthToken()->accessToken($this->tokenRefresher),
            $package->metadata(Metadata::GITHUB_REPO_NAME),
            $this->router->generate('package_webhook', ['package' => $package->id()->toString()], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $package->webhookWasRemoved();
    }
}
