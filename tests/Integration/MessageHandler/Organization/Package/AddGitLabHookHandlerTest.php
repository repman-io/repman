<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\AddGitLabHook;
use Buddy\Repman\MessageHandler\Organization\Package\AddGitLabHookHandler;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class AddGitLabHookHandlerTest extends IntegrationTestCase
{
    public function testAddHook(): void
    {
        $userId = $this->fixtures->createUser();
        $organizationId = $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'gitlab');
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.works', 'gitlab-oauth', [Metadata::GITLAB_PROJECT_ID => 123]);

        $handler = $this->container()->get(AddGitLabHookHandler::class);
        $handler->__invoke(new AddGitLabHook($packageId));
        $this->container()->get('doctrine.orm.entity_manager')->flush();

        $package = $this
            ->container()
            ->get(PackageQuery::class)
            ->getById($packageId);

        self::assertInstanceOf(\DateTimeImmutable::class, $package->get()->webhookCreatedAt());
    }
}
