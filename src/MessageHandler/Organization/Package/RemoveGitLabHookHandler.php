<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\RemoveGitLabHook;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RemoveGitLabHookHandler extends AbstractHookHandler
{
    public function __invoke(RemoveGitLabHook $message): void
    {
        $package = $this->packages->getById(Uuid::fromString($message->packageId()));
        $this->integrations->gitLabApi()->removeHook(
            $package->oauthToken()->accessToken($this->tokenRefresher),
            $package->metadata(Metadata::GITLAB_PROJECT_ID),
            $this->router->generate('package_webhook', ['package' => $package->id()->toString()], UrlGeneratorInterface::ABSOLUTE_URL)
        );
        $package->webhookWasRemoved();
    }
}
