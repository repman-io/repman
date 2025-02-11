<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\RemoveBitbucketHook;
use Buddy\Repman\MessageHandler\Organization\Package\RemoveBitbucketHookHandler;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class RemoveBitbucketHookHandlerTest extends IntegrationTestCase
{
    public function testRemoveHook(): void
    {
        $userId = $this->fixtures->createUser();
        $organizationId = $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'bitbucket');
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.works', 'bitbucket-oauth', [Metadata::BITBUCKET_REPO_NAME => 'buddy-works/repman']);
        $this->fixtures->setWebhookCreated($packageId);

        $handler = $this->container()->get(RemoveBitbucketHookHandler::class);
        $handler->__invoke(new RemoveBitbucketHook($packageId));
        $this->container()->get('doctrine.orm.entity_manager')->flush();

        $package = $this
            ->container()
            ->get(PackageQuery::class)
            ->getById($packageId);

        $this->assertEquals(null, $package->get()->webhookCreatedAt());
    }
}
