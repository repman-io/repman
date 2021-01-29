<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\AddGitHubHook;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AddGitHubHookHandler extends AbstractHookHandler
{
    public function __invoke(AddGitHubHook $message): void
    {
        $package = $this->packages->find(Uuid::fromString($message->packageId()));
        if (!$package instanceof Package) {
            return;
        }

        try {
            $this->integrations->gitHubApi()->addHook(
                $package->oauthToken()->accessToken($this->tokenRefresher),
                $package->metadata(Metadata::GITHUB_REPO_NAME),
                $this->router->generate('package_webhook', ['package' => $package->id()->toString()], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            $package->webhookWasCreated();
        } catch (\Throwable $exception) {
            $package->webhookWasNotCreated($exception->getMessage());
        }
    }
}
