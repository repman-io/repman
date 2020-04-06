<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

final class OrganizationControllerTest extends FunctionalTestCase
{
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createAndLoginAdmin();
    }

    public function testSuccessfulCreate(): void
    {
        $this->client->request('GET', $this->urlTo('organization_create'));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Create a new organization', $this->lastResponseBody());

        $this->client->submitForm('Create a new organization', ['name' => 'Acme Inc.']);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_overview', ['organization' => 'acme-inc'])));

        $this->client->followRedirect();

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Organization &quot;Acme Inc.&quot; has been created', $this->lastResponseBody());
    }

    public function testNameCantBeEmpty(): void
    {
        $this->client->request('GET', $this->urlTo('organization_create'));

        $this->client->followRedirects();
        $this->client->submitForm('Create a new organization', ['name' => '']);

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('This value should not be blank', $this->lastResponseBody());
    }

    public function testInvalidName(): void
    {
        $this->client->request('GET', $this->urlTo('organization_create'));

        $this->client->followRedirects();
        $this->client->submitForm('Create a new organization', ['name' => '!@#']); // only special chars

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Name cannot consist of special characters only.', $this->lastResponseBody());
    }

    public function testUniqueness(): void
    {
        $this->client->request('GET', $this->urlTo('organization_create'));
        $this->client->followRedirects();
        $this->client->submitForm('Create a new organization', ['name' => 'same']);

        $this->client->request('GET', $this->urlTo('organization_create'));
        $this->client->submitForm('Create a new organization', ['name' => 'same']);

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Organization &quot;same&quot; already exists', $this->lastResponseBody());
    }

    public function testOverview(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_overview', ['organization' => 'buddy']));

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testOverviewNotAllowedForNotOwnedOrganization(): void
    {
        $otherId = $this->fixtures->createAdmin('cto@buddy.works', 'strong');
        $this->fixtures->createOrganization('buddy', $otherId);
        $this->client->request('GET', $this->urlTo('organization_overview', ['organization' => 'buddy']));

        self::assertTrue($this->client->getResponse()->isForbidden());
    }

    public function testPackages(): void
    {
        $anotherUserID = $this->fixtures->createUser('another@user.com', 'secret');

        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $anotherOrgId = $this->fixtures->createOrganization('google', $anotherUserID);

        $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->addPackage($anotherOrgId, 'https://google.com');

        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy']));

        self::assertTrue($this->client->getResponse()->isOk());

        self::assertStringContainsString(
            '1 entries',
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testAddPackage(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy']));

        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->submitForm('Add', [
            'url' => 'http://guthib.com',
            'type' => 'vcs',
        ]);

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_packages', ['organization' => 'buddy']))
        );
        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        self::assertCount(1, $transport->getSent());
        self::assertInstanceOf(SynchronizePackage::class, $transport->getSent()[0]->getMessage());

        $this->client->followRedirect();
        self::assertStringContainsString('Package has been added', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testRemovePackage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new \DateTimeImmutable());

        $this->client->followRedirects(true);
        $this->client->request('DELETE', $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertStringContainsString(
            'Package has been successfully removed',
            $this->lastResponseBody()
        );
    }

    public function testRemoveBitbucketPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_BITBUCKET);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'bitbucket-oauth', [Metadata::BITBUCKET_REPO_NAME => 'some/repo']);
        $this->fixtures->setWebhookCreated($packageId);

        $this->client->followRedirects();
        $this->client->request('DELETE', $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertStringContainsString('Package has been successfully removed', $this->lastResponseBody());
    }

    public function testRemoveGitHubPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'some/repo']);
        $this->fixtures->setWebhookCreated($packageId);

        $this->client->followRedirects();
        $this->client->request('DELETE', $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertStringContainsString('Package has been successfully removed', $this->lastResponseBody());
    }

    public function testRemoveGitLabPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'gitlab-oauth', [Metadata::GITLAB_PROJECT_ID => 123]);
        $this->fixtures->setWebhookCreated($packageId);

        $this->client->followRedirects();
        $this->client->request('DELETE', $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertStringContainsString('Package has been successfully removed', $this->lastResponseBody());
    }

    public function testUpdatePackage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');

        $this->client->request('POST', $this->urlTo('organization_package_update', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertTrue($this->client->getResponse()->isRedirect(
            $this->urlTo('organization_packages', ['organization' => 'buddy'])
        ));

        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/repman', 'Repository manager', '2.1.1', new \DateTimeImmutable('2020-01-01 12:12:12'));

        $this->client->followRedirect();
        self::assertStringContainsString('Package will be updated in the background', $this->lastResponseBody());
        self::assertStringContainsString('buddy-works/repman', $this->lastResponseBody());
        self::assertStringContainsString('2.1.1', $this->lastResponseBody());
    }

    public function testUpdateNonExistingPackage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');

        $this->client->request('POST', $this->urlTo('organization_package_update', [
            'organization' => 'buddy',
            'package' => Uuid::uuid4()->toString(), // random
        ]));

        self::assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testSynchronizationError(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithError($packageId, 'Connection error: 503 service unavailable');

        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy']));

        self::assertStringContainsString('Synchronization error', $this->lastResponseBody());
        self::assertStringContainsString('Connection error: 503 service unavailable', $this->lastResponseBody());
    }

    public function testRemoveNonExistingPackage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');

        $this->client->request('DELETE', $this->urlTo('organization_package_update', [
            'organization' => 'buddy',
            'package' => Uuid::uuid4()->toString(), // random
        ]));

        self::assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testPackageStats(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->addPackageDownload(3, $packageId);

        $this->client->request('GET', $this->urlTo('organization_package_stats', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Total installs: 3', $this->lastResponseBody());
    }

    public function testPackageWebhookPage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');

        $this->client->request('GET', $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString($this->urlTo('package_webhook', ['package' => $packageId]), $this->lastResponseBody());
    }

    public function testOrganizationStats(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->addPackageDownload(3, $packageId);

        $this->client->request('GET', $this->urlTo('organizations_stats', [
            'organization' => 'buddy',
        ]));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Total installs: 3', $this->lastResponseBody());
    }

    public function testGenerateNewToken(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_token_new', ['organization' => 'buddy']));
        $this->client->submitForm('Generate', [
            'name' => 'Production Token',
        ]);

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_tokens', ['organization' => 'buddy']))
        );

        $this->client->followRedirect();
        self::assertStringContainsString('Production Token', $this->lastResponseBody());
    }

    public function testRegenerateToken(): void
    {
        $this->fixtures->createToken(
            $this->fixtures->createOrganization('buddy', $this->userId),
            'secret-token'
        );
        $this->container()->get(TokenGenerator::class)->setNextToken('regenerated-token');
        $this->client->request('POST', $this->urlTo('organization_token_regenerate', [
            'organization' => 'buddy',
            'token' => 'secret-token',
        ]));

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_tokens', ['organization' => 'buddy']))
        );
        $this->client->followRedirect();
        self::assertStringContainsString('regenerated-token', $this->lastResponseBody());
    }

    public function testRemoveToken(): void
    {
        $this->fixtures->createToken(
            $this->fixtures->createOrganization('buddy', $this->userId),
            'secret-token'
        );
        $this->client->request('DELETE', $this->urlTo('organization_token_remove', [
            'organization' => 'buddy',
            'token' => 'secret-token',
        ]));

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_tokens', ['organization' => 'buddy']))
        );
        $this->client->followRedirect();
        self::assertStringNotContainsString('secret-token', $this->lastResponseBody());
    }

    public function testSettings(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_settings', ['organization' => 'buddy']));

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testRemoveOrganization(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy inc', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new \DateTimeImmutable());

        $this->client->request('DELETE', $this->urlTo('organization_remove', [
            'organization' => 'buddy-inc',
        ]));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->followRedirect();

        self::assertStringContainsString('Organization buddy inc has been successfully removed', $this->lastResponseBody());
    }

    public function testRemoveForbiddenOrganization(): void
    {
        $otherId = $this->fixtures->createAdmin('cto@buddy.works', 'strong');
        $this->fixtures->createOrganization('buddy', $otherId);

        $this->client->request('DELETE', $this->urlTo('organization_remove', [
            'organization' => 'buddy',
        ]));

        self::assertTrue($this->client->getResponse()->isForbidden());
    }
}
