<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\RemoveGitLabHook;
use Buddy\Repman\Repository\PackageRepository;
use Buddy\Repman\Service\GitLabApi;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RemoveGitLabHookHandler implements MessageHandlerInterface
{
    private PackageRepository $packages;
    private GitLabApi $api;
    private UrlGeneratorInterface $router;

    public function __construct(PackageRepository $packages, GitLabApi $api, UrlGeneratorInterface $router)
    {
        $this->packages = $packages;
        $this->api = $api;
        $this->router = $router;
    }

    public function __invoke(RemoveGitLabHook $message): void
    {
        $package = $this->packages->getById(Uuid::fromString($message->packageId()));
        $this->api->removeHook(
            $package->oauthToken(),
            $package->metadata(Metadata::GITLAB_PROJECT_ID),
            $this->router->generate('package_webhook', ['package' => $package->id()->toString()], UrlGeneratorInterface::ABSOLUTE_URL)
        );
        $package->webhookWasRemoved();
    }
}
