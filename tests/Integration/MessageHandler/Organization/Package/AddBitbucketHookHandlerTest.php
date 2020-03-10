<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Buddy\Repman\MessageHandler\Organization\Package\AddBitbucketHookHandler;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class AddBitbucketHookHandlerTest extends IntegrationTestCase
{
    public function testAddHook(): void
    {
        $userId = $this->fixtures->createUser();
        $organizationId = $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'bitbucket');
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.works', 'bitbucket-oauth', [Metadata::BITBUCKET_REPO_NAME => 'buddy-works/repman']);

        $handler = $this->container()->get(AddBitbucketHookHandler::class);
        $handler->__invoke(new AddBitbucketHook($packageId));
        $this->container()->get('doctrine.orm.entity_manager')->flush();

        $package = $this
            ->container()
            ->get(PackageQuery::class)
            ->getById($packageId);

        self::assertInstanceOf(\DateTimeImmutable::class, $package->get()->webhookCreatedAt());
    }
}
