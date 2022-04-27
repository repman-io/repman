<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Buddy\Repman\MessageHandler\Organization\Package\AddBitbucketHookHandler;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Service\Integration\BitbucketApi;
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

        $package = $this->container()->get(PackageQuery::class)->getById($packageId);

        self::assertInstanceOf(\DateTimeImmutable::class, $package->get()->webhookCreatedAt());

        $this->container()->get(BitbucketApi::class)->setExceptionOnNextCall(
            new \RuntimeException($error = 'Repository was archived so is read-only.')
        );
        $handler->__invoke(new AddBitbucketHook($packageId));
        $this->container()->get('doctrine.orm.entity_manager')->flush();

        $package = $this->container()->get(PackageQuery::class)->getById($packageId);

        self::assertStringContainsString($error, (string) $package->get()->webhookCreatedError());
    }

    public function testHandlePackageNotFoundWithoutError(): void
    {
        $exception = null;
        try {
            $handler = $this->container()->get(AddBitbucketHookHandler::class);
            $handler->__invoke(new AddBitbucketHook('e0ea4d32-4144-4a67-9310-6dae483a6377'));
        } catch (\Exception $exception) {
        }

        self::assertNull($exception);
    }
}
