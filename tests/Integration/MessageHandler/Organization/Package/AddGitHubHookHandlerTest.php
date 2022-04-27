<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Organization\Package;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Message\Organization\Package\AddGitHubHook;
use Buddy\Repman\MessageHandler\Organization\Package\AddGitHubHookHandler;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Service\Integration\GitHubApi;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;

final class AddGitHubHookHandlerTest extends IntegrationTestCase
{
    public function testAddHook(): void
    {
        $userId = $this->fixtures->createUser();
        $organizationId = $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'github');
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.works', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'buddy/works']);

        $handler = $this->container()->get(AddGitHubHookHandler::class);
        $handler->__invoke(new AddGitHubHook($packageId));
        $this->container()->get('doctrine.orm.entity_manager')->flush();

        $package = $this->container()->get(PackageQuery::class)->getById($packageId);

        self::assertInstanceOf(\DateTimeImmutable::class, $package->get()->webhookCreatedAt());

        $this->container()->get(GitHubApi::class)->setExceptionOnNextCall(
            new \RuntimeException($error = 'Repository was archived so is read-only.')
        );
        $handler->__invoke(new AddGitHubHook($packageId));
        $this->container()->get('doctrine.orm.entity_manager')->flush();

        $package = $this->container()->get(PackageQuery::class)->getById($packageId);

        self::assertStringContainsString($error, (string) $package->get()->webhookCreatedError());
    }

    public function testHandlePackageNotFoundWithoutError(): void
    {
        $exception = null;
        try {
            $handler = $this->container()->get(AddGitHubHookHandler::class);
            $handler->__invoke(new AddGitHubHook('e0ea4d32-4144-4a67-9310-6dae483a6377'));
        } catch (\Exception $exception) {
        }

        self::assertNull($exception);
    }
}
