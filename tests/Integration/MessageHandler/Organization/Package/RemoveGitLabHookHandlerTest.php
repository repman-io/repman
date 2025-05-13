<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\RemoveGitLabHook;
use Buddy\Repman\MessageHandler\Organization\Package\RemoveGitLabHookHandler;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class RemoveGitLabHookHandlerTest extends IntegrationTestCase
{
    public function testAddHook(): void
    {
        $userId = $this->fixtures->createUser();
        $organizationId = $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'gitlab');
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.works', 'gitlab-oauth', [Metadata::GITLAB_PROJECT_ID => 123]);
        $this->fixtures->setWebhookCreated($packageId);

        $handler = $this->container()->get(RemoveGitLabHookHandler::class);
        $handler->__invoke(new RemoveGitLabHook($packageId));
        $this->container()->get('doctrine.orm.entity_manager')->flush();

        $package = $this
            ->container()
            ->get(PackageQuery::class)
            ->getById($packageId);

        $this->assertEquals(null, $package->get()->webhookCreatedAt());
    }
}
