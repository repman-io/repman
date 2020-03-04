<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization;

use Buddy\Repman\Entity\Organization\Package;
use Buddy\Repman\Entity\User\OauthToken;
use Buddy\Repman\Message\Organization\AddHook;
use Buddy\Repman\Service\GitHubApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AddHookHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private GithubApi $api;

    public function __construct(EntityManagerInterface $em, GithubApi $api)
    {
        $this->em = $em;
        $this->api = $api;
    }

    public function __invoke(AddHook $message): void
    {
        /** @var Package */
        $package = $this->em
            ->getRepository(Package::class)
            ->find($message->packageId());

        /** @var OauthToken */
        $token = $package->oauthToken();

        $this->api->addHook(
            $token->value(),
            $message->repoName(),
            $message->url()
        );

        $package->webhookWasCreated();
    }
}
