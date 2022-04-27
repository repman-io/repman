<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\RemoveGitHubHook;
use Buddy\Repman\MessageHandler\Organization\Package\RemoveGitHubHookHandler;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class RemoveGitHubHookHandlerTest extends IntegrationTestCase
{
    public function testRemoveHook(): void
    {
        $userId = $this->fixtures->createUser();
        $organizationId = $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'github');
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.works', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'buddy/works']);
        $this->fixtures->setWebhookCreated($packageId);

        $handler = $this->container()->get(RemoveGitHubHookHandler::class);
        $handler->__invoke(new RemoveGitHubHook($packageId));
        $this->container()->get('doctrine.orm.entity_manager')->flush();

        $package = $this
            ->container()
            ->get(PackageQuery::class)
            ->getById($packageId);

        self::assertEquals(null, $package->get()->webhookCreatedAt());
    }

    public function testPackageNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package 9691e73b-1738-42fe-9d5b-31d0dadf7407 not found.');

        $handler = $this->container()->get(RemoveGitHubHookHandler::class);
        $handler->__invoke(new RemoveGitHubHook('9691e73b-1738-42fe-9d5b-31d0dadf7407'));
    }
}
