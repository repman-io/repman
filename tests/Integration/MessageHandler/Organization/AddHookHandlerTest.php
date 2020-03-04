<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization;

use Buddy\Repman\Message\Organization\AddHook;
use Buddy\Repman\MessageHandler\Organization\AddHookHandler;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class AddHookHandlerTest extends IntegrationTestCase
{
    public function testAddHook(): void
    {
        $packageUrl = 'https://buddy.works';
        $tokenType = 'github';

        $userId = $this->fixtures->createUser();
        $organizationId = $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, $tokenType);
        $packageId = $this->fixtures->addPackage($organizationId, $packageUrl, 'github-oauth');

        $handler = $this->container()->get(AddHookHandler::class);
        $handler(new AddHook(
            $packageId,
            'buddy/repman',
            $packageUrl
        ));

        $this->container()->get('doctrine.orm.entity_manager')->flush();

        $package = $this
            ->container()
            ->get(PackageQuery::class)
            ->getById($packageId);

        self::assertInstanceOf(\DateTimeImmutable::class, $package->get()->webhookCreatedAt());
    }
}
