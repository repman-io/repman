<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Entity\Organization\Package\Version;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\Query\User\Model\Package\Link;
use Buddy\Repman\Query\User\OrganizationQuery\DbalOrganizationQuery;
use Buddy\Repman\Query\User\PackageQuery;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Service\Integration\BitbucketApi;
use Buddy\Repman\Service\Integration\GitHubApi;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use function Ramsey\Uuid\v4;

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

    public function testPackageList(): void
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

    public function testPackageSearch(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);

        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/testing', '1', '1.1.1', new \DateTimeImmutable());

        $packageId2 = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId2, 'buddy-works/example', '2', '1.1.1', new \DateTimeImmutable());

        // Check both packages are returned first
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy']));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString(
            '2 entries',
            (string) $this->client->getResponse()->getContent()
        );

        // Search for 'testing' (which is in name)
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'search' => 'testing']));

        self::assertTrue($this->client->getResponse()->isOk());
        $response = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('1 entries', $response);
        self::assertStringContainsString($packageId, $response);
        self::assertStringNotContainsString($packageId2, $response);

        // Search for '2' (which is in description)
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'search' => '2']));

        self::assertTrue($this->client->getResponse()->isOk());
        $response = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('1 entries', $response);
        self::assertStringContainsString($packageId2, $response);
        self::assertStringNotContainsString($packageId, $response);

        // Test serach query params passing
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'search' => 'buddy', 'limit' => 1]));
        self::assertTrue($this->client->getResponse()->isOk());
        $response = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('2 entries', $response);
        self::assertStringContainsString('search=buddy', $response);
    }

    public function testDependantSearch(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);

        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/testing', '1', '1.1.1', new \DateTimeImmutable());

        $packageId2 = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $links = [
            new Link('requires', 'buddy-works/testing', '^1.5'),
        ];
        $this->fixtures->syncPackageWithData($packageId2, 'buddy-works/example', '2', '1.1.1', new \DateTimeImmutable(), [], $links);

        // Search for 'testing' (which is in name)
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'search' => 'depends:buddy-works/testing']));

        self::assertTrue($this->client->getResponse()->isOk());
        $response = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('1 entries', $response);
        self::assertStringNotContainsString($packageId, $response);
        self::assertStringContainsString($packageId2, $response);
    }

    public function testPagination(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);

        for ($i = 0; $i < 111; ++$i) {
            $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        }

        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1]));

        self::assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Showing 1 to 1 of 111 entries', $content);
        self::assertStringContainsString('offset=111&amp;limit=1', $content);

        // Invalid limit (too low)
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => -1]));

        self::assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Showing 1 to 20 of 111 entries', $content);
        self::assertStringContainsString('offset=100&amp;limit=20', $content);

        // Invalid limit (too high)
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 101]));

        self::assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Showing 1 to 100 of 111 entries', $content);
        self::assertStringContainsString('offset=100&amp;limit=100', $content);

        // Negative offset
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'offset' => -1]));

        self::assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Showing 1 to 20 of 111 entries', $content);
        self::assertStringContainsString('offset=0&amp;limit=20', $content);
    }

    public function testSorting(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);

        for ($i = 1; $i < 6; ++$i) {
            $submissionTime = (new \DateTimeImmutable())->add(new \DateInterval("P{$i}D"));

            $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
            $this->fixtures->syncPackageWithData($packageId, 'buddy-works/package-'.$i, 'Test', "1.{$i}", $submissionTime);
        }

        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1]));

        self::assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('buddy-works/package-1', $content);
        self::assertStringContainsString('sort=name:desc', $content);
        self::assertStringContainsString('sort=version:asc', $content);
        self::assertStringContainsString('sort=date:asc', $content);

        // Sort by name desc
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1, 'sort' => 'name:desc']));

        self::assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('buddy-works/package-5', $content);
        self::assertStringContainsString('sort=name:asc', $content);
        self::assertStringContainsString('sort=version:asc', $content);
        self::assertStringContainsString('sort=date:asc', $content);

        // Sort by version desc
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1, 'sort' => 'version:desc']));

        self::assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('buddy-works/package-5', $content);
        self::assertStringContainsString('sort=name:asc', $content);
        self::assertStringContainsString('sort=version:desc', $content);
        self::assertStringContainsString('sort=date:asc', $content);

        // Sort by released date asc
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1, 'sort' => 'date:asc']));

        self::assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('buddy-works/package-1', $content);
        self::assertStringContainsString('sort=name:asc', $content);
        self::assertStringContainsString('sort=version:asc', $content);
        self::assertStringContainsString('sort=date:desc', $content);

        // Sort by invalid column
        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1, 'sort' => 'invalid-column:asc']));

        self::assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('buddy-works/package-1', $content);
        self::assertStringContainsString('sort=name:asc', $content);
        self::assertStringContainsString('sort=version:asc', $content);
        self::assertStringContainsString('sort=date:asc', $content);
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

        $this->fixtures->prepareRepoFiles();
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

        $this->client->disableReboot();
        $this->client->followRedirects();
        $this->client->request('DELETE', $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertStringContainsString('Package has been successfully removed', $this->lastResponseBody());
        self::assertEquals(['some/repo'], $this->container()->get(GitHubApi::class)->removedWebhooks());
    }

    public function testRemoveGitHubPackageAndIgnoreWebhookError(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'some/repo']);
        $this->fixtures->setWebhookCreated($packageId);
        $this->container()->get(GitHubApi::class)->setExceptionOnNextCall(new \RuntimeException('Bad credentials'));

        $this->client->followRedirects();
        $this->client->request('DELETE', $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertStringContainsString('Package has been successfully removed', $this->lastResponseBody());
        self::assertStringContainsString('Webhook removal failed due to', $this->lastResponseBody());
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

    public function testSynchronizeWebhookFromGitHubPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'some/repo']);

        $this->client->followRedirects();
        $this->client->request('POST', $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertStringContainsString('will be synchronized in background', $this->lastResponseBody());
    }

    public function testSynchronizeWebhookFromGitLabPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'gitlab-oauth', [Metadata::GITLAB_PROJECT_ID => 123]);

        $this->client->followRedirects();
        $this->client->request('POST', $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertStringContainsString('will be synchronized in background', $this->lastResponseBody());
    }

    public function testSynchronizeWebhookFromBitbucketPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'bitbucket-oauth', [Metadata::BITBUCKET_REPO_NAME => 'some/repo']);

        $this->client->followRedirects();
        $this->client->request('POST', $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertStringContainsString('will be synchronized in background', $this->lastResponseBody());
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

        $this->client->request('DELETE', $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => Uuid::uuid4()->toString(), // random
        ]));

        self::assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testRemoveNotOwnedPackage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $buddyPackageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $repmanId = $this->fixtures->createOrganization('repman', $this->userId);
        $repmanPackageId = $this->fixtures->addPackage($repmanId, 'https://repman.io');

        $this->client->request('DELETE', $this->urlTo('organization_package_remove', [
            'organization' => 'repman',
            'package' => $buddyPackageId, // package from other organization
        ]));

        self::assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testPackageDetails(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $versions = [
            new Version(Uuid::uuid4(), '1.0.0', 'someref', 1234, new \DateTimeImmutable(), Version::STABILITY_STABLE),
            new Version(Uuid::uuid4(), '1.0.1', 'ref2', 1048576, new \DateTimeImmutable(), Version::STABILITY_STABLE),
            new Version(Uuid::uuid4(), '1.1.0', 'lastref', 1073741824, new \DateTimeImmutable(), Version::STABILITY_STABLE),
        ];
        $links = [
            new Link('requires', 'buddy-works/target', '^1.5'),
            new Link('suggests', 'buddy-works/buddy', '^2.0'), // Suggest self to test dependant link
        ];
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new \DateTimeImmutable(), $versions, $links, 'This is a readme');
        $this->fixtures->addScanResult($packageId, 'ok');

        $crawler = $this->client->request('GET', $this->urlTo('organization_package_details', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('buddy-works/buddy details', $this->lastResponseBody());
        self::assertStringContainsString('Test', $this->lastResponseBody());
        self::assertStringContainsString('Available versions', $this->lastResponseBody());
        foreach ($versions as $version) {
            self::assertStringContainsString($version->version(), $this->lastResponseBody());
            self::assertStringContainsString($version->reference(), $this->lastResponseBody());
        }

        $crawlerText = $crawler->text(null, true);

        self::assertStringContainsString('Requirements', $this->lastResponseBody());
        foreach ($links as $link) {
            self::assertStringContainsString("{$link->target()}: {$link->constraint()}", $crawlerText);
        }

        self::assertStringContainsString('Dependant Packages 1', $crawlerText);
        self::assertStringContainsString('depends:buddy-works/buddy', $this->lastResponseBody());

        self::assertStringContainsString('This is a readme', $this->lastResponseBody());
        self::assertStringNotContainsString('This package is <b>abandoned</b>', $this->lastResponseBody());

        $this->client->request('GET', $this->urlTo('organization_package_details', [
            'organization' => 'buddy',
            'package' => v4(),
        ]));

        self::assertTrue($this->client->getResponse()->isNotFound());
    }

    /**
     * @dataProvider getAbandonedReplacements
     */
    public function testPackageDetailsAbandoned(string $replacementPackage, string $expectedMessage): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new \DateTimeImmutable(), [], [], null, $replacementPackage);

        $this->client->request('GET', $this->urlTo('organization_package_details', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString($expectedMessage, $this->lastResponseBody());
    }

    /**
     * @return \Generator<array<mixed>>
     */
    public function getAbandonedReplacements(): \Generator
    {
        yield 'Abandoned without replacement package' => [
            '',
            'This package is <b>abandoned</b> and no longer maintained. No replacement package was suggested.',
        ];

        yield 'Abandoned with replacement package' => [
            'foo/bar',
            'This package is <b>abandoned</b> and no longer maintained. The author suggests using the <b>foo/bar</b> package instead.',
        ];
    }

    public function testPackageStats(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->addPackageDownload(3, $packageId, $version = '1.2.3');

        $crawler = $this->client->request('GET', $this->urlTo('organization_package_stats', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Total installs: 3', $crawler->text(null, true));

        $this->client->request('GET', $this->urlTo('organization_package_version_stats', [
            'organization' => 'buddy',
            'package' => $packageId,
            'version' => $version,
        ]));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('{"x":"'.date('Y-m-d').'","y":3}', $this->lastResponseBody());
    }

    public function testPackageWebhookPage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'buddy/works']);

        $this->client->request('POST', '/hook/'.$packageId);
        $this->client->request('GET', $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString($this->urlTo('package_webhook', ['package' => $packageId]), $this->lastResponseBody());
        // last requests table is visible
        self::assertStringContainsString('User agent', $this->lastResponseBody());

        $this->fixtures->setWebhookError($packageId, 'Repository was archived so is read-only.');

        $this->client->request('GET', $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));
        self::assertStringContainsString('Repository was archived so is read-only.', $this->lastResponseBody());
    }

    public function testOrganizationStats(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->addPackageDownload(3, $packageId);

        $crawler = $this->client->request('GET', $this->urlTo('organizations_stats', [
            'organization' => 'buddy',
        ]));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Total installs: 3', $crawler->text(null, true));
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

    public function testChangeName(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->followRedirects();
        $this->client->request('GET', $this->urlTo('organization_settings', ['organization' => 'buddy']));
        $this->client->submitForm('Rename', [
            'name' => 'Meat',
        ]);

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Meat', $this->lastResponseBody());
        self::assertStringContainsString('Organization name been successfully changed.', $this->lastResponseBody());
    }

    public function testChangeAlias(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->followRedirects();
        $this->client->request('GET', $this->urlTo('organization_settings', ['organization' => 'buddy']));
        $this->client->submitForm('Change', [
            'alias' => 'repman',
        ]);

        $organization = $this
            ->container()
            ->get(OrganizationRepository::class)
            ->getById(Uuid::fromString($organizationId));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Organization alias has been successfully changed.', $this->lastResponseBody());
        self::assertEquals('repman', $organization->alias());
    }

    public function testChangeAliasWithInvalidChars(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->followRedirects();
        $this->client->request('GET', $this->urlTo('organization_settings', ['organization' => 'buddy']));
        $this->client->submitForm('Change', [
            'alias' => 'https://repman',
        ]);

        self::assertStringContainsString('Alias can contain only alphanumeric characters and _ or - sign', $this->lastResponseBody());
        self::assertStringNotContainsString('Organization alias has been successfully changed.', $this->lastResponseBody());
    }

    public function testChangeAnonymousAccess(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->followRedirects();

        $organization = $this
            ->container()
            ->get(DbalOrganizationQuery::class)
            ->getByAlias('buddy')
            ->get();

        self::assertFalse($organization->hasAnonymousAccess());

        $this->client->request('GET', $this->urlTo('organization_settings', ['organization' => 'buddy']));
        $this->client->submitForm('changeAnonymousAccess', [
            'hasAnonymousAccess' => true,
        ]);

        $organization = $this
            ->container()
            ->get(DbalOrganizationQuery::class)
            ->getByAlias('buddy')
            ->get();

        self::assertTrue($organization->hasAnonymousAccess());
        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Anonymous access has been successfully changed.', $this->lastResponseBody());
    }

    public function testRemoveOrganization(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy inc', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_BITBUCKET);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new \DateTimeImmutable());
        $this->fixtures->setWebhookCreated($this->fixtures->addPackage($organizationId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'org/repo']));
        $this->fixtures->setWebhookCreated($this->fixtures->addPackage($organizationId, 'https://buddy.com', 'bitbucket-oauth', [Metadata::BITBUCKET_REPO_NAME => 'webhook/problem']));
        $this->container()->get(BitbucketApi::class)->setExceptionOnNextCall(new \RuntimeException('Repository was archived'));

        $this->client->request('DELETE', $this->urlTo('organization_remove', [
            'organization' => 'buddy-inc',
        ]));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->disableReboot();
        $this->client->followRedirect();

        self::assertStringContainsString('Organization buddy inc has been successfully removed', $this->lastResponseBody());
        self::assertStringContainsString('Repository was archived', $this->lastResponseBody());
        self::assertEquals(0, $this->container()->get(PackageQuery::class)->count($organizationId, new PackageQuery\Filter()));
        self::assertEquals(['org/repo'], $this->container()->get(GitHubApi::class)->removedWebhooks());
        self::assertEquals([], $this->container()->get(BitbucketApi::class)->removedWebhooks());
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

    public function testPackageEmptyScanResults(): void
    {
        $organization = 'buddy';
        $buddyId = $this->fixtures->createOrganization($organization, $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');

        $this->client->request('GET', $this->urlTo('organization_package_scan_results', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        self::assertStringContainsString('package not scanned yet', $this->lastResponseBody());
    }

    public function testScanPackages(): void
    {
        $organization = 'buddy';
        $buddyId = $this->fixtures->createOrganization($organization, $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $package2Id = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/repman', 'Repository manager', '2.1.1', new \DateTimeImmutable('2020-01-01 12:12:12'));
        $this->fixtures->syncPackageWithData($package2Id, 'buddy-works/repman2', 'Repository manager', '2.1.1', new \DateTimeImmutable('2020-01-01 12:12:12'));

        $this->client->request('POST', $this->urlTo('organization_package_scan', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        self::assertTrue($this->client->getResponse()->isRedirect(
            $this->urlTo('organization_packages', ['organization' => $organization])
        ));

        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        self::assertCount(3, $transport->getSent());
        self::assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());

        $this->fixtures->addScanResult($packageId, 'ok');
        $this->fixtures->addScanResult($package2Id, 'error', [
            'exception' => [
                'RuntimeException' => 'Some error',
            ],
        ]);

        $this->client->followRedirect();
        self::assertStringContainsString('Package will be scanned in the background', $this->lastResponseBody());
        self::assertStringContainsString('ok', $this->lastResponseBody());
        self::assertStringContainsString('no advisories', $this->lastResponseBody());
        self::assertStringContainsString('error', $this->lastResponseBody());
        self::assertStringContainsString('&lt;b&gt;RuntimeException&lt;/b&gt; - Some error', $this->lastResponseBody());
    }

    public function testPackageScanResultsWithOkStatus(): void
    {
        $organization = 'buddy';
        $version = '1.2.3';

        $buddyId = $this->fixtures->createOrganization($organization, $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData(
            $packageId,
            'buddy-works/repman',
            'Repository manager',
            $version,
            new \DateTimeImmutable()
        );

        $this->fixtures->addScanResult($packageId, 'ok');

        $this->client->request('GET', $this->urlTo('organization_package_scan_results', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        self::assertStringContainsString($version, $this->lastResponseBody());
        self::assertStringContainsString('ok', $this->lastResponseBody());
        self::assertStringContainsString('no advisories', $this->lastResponseBody());
    }

    public function testPackageScanResultsWithWarningStatus(): void
    {
        $organization = 'buddy';
        $version = '1.2.3';

        $buddyId = $this->fixtures->createOrganization($organization, $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData(
            $packageId,
            'buddy-works/repman',
            'Repository manager',
            $version,
            new \DateTimeImmutable()
        );

        $this->fixtures->addScanResult($packageId, 'warning', [
            'composer.lock' => [
                'vendor/some-dependency' => [
                    'version' => '6.6.6',
                    'advisories' => [
                        [
                            'title' => 'Direct access of ESI URLs behind a trusted proxy',
                            'cve' => 'CVE-2014-5245',
                            'link' => 'https://symfony.com/cve-2014-5245',
                        ],
                    ],
                ],
            ],
            'sub-dir/composer.lock' => [],
        ]);

        $crawler = $this->client->request('GET', $this->urlTo('organization_package_scan_results', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        self::assertStringContainsString($version, $this->lastResponseBody());
        self::assertStringContainsString('buddy-works/repman security scan results', $crawler->text(null, true));
        self::assertStringContainsString('warning', $this->lastResponseBody());
        self::assertStringContainsString('vendor/some-dependency', $this->lastResponseBody());
        self::assertStringContainsString('6.6.6', $this->lastResponseBody());
        self::assertStringContainsString('Direct access of ESI URLs behind a trusted proxy', $this->lastResponseBody());
        self::assertStringContainsString('CVE-2014-5245', $this->lastResponseBody());
        self::assertStringContainsString('https://symfony.com/cve-2014-5245', $this->lastResponseBody());
        self::assertStringNotContainsString('sub-dir/composer.lock', $this->lastResponseBody());
    }

    public function testPackageScanResultsWithErrorStatus(): void
    {
        $organization = 'buddy';
        $version = '1.2.3';

        $buddyId = $this->fixtures->createOrganization($organization, $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData(
            $packageId,
            'buddy-works/repman',
            'Repository manager',
            $version,
            new \DateTimeImmutable()
        );

        $this->fixtures->addScanResult($packageId, 'error', [
            'exception' => [
                'RuntimeException' => 'Some error',
            ],
        ]);

        $this->client->request('GET', $this->urlTo('organization_package_scan_results', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        self::assertStringContainsString($version, $this->lastResponseBody());
        self::assertStringContainsString('error', $this->lastResponseBody());
        self::assertStringContainsString('<b>RuntimeException</b> - Some error', $this->lastResponseBody());
    }

    public function testPackageScanResultsWithNaStatus(): void
    {
        $organization = 'buddy';
        $version = '1.2.3';

        $buddyId = $this->fixtures->createOrganization($organization, $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData(
            $packageId,
            'buddy-works/repman',
            'Repository manager',
            $version,
            new \DateTimeImmutable()
        );

        $this->fixtures->addScanResult($packageId, 'n/a', []);

        $this->client->request('GET', $this->urlTo('organization_package_scan_results', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        self::assertStringContainsString($version, $this->lastResponseBody());
        self::assertStringContainsString('n/a', $this->lastResponseBody());
        self::assertStringContainsString('composer.lock not present', $this->lastResponseBody());
    }

    public function testOverviewAllowedForAnonymousUser(): void
    {
        $otherId = $this->fixtures->createAdmin('cto@buddy.works', 'strong');
        $organizationId = $this->fixtures->createOrganization('public', $otherId);

        $this->fixtures->enableAnonymousUserAccess($organizationId);

        if (static::$booted) {
            self::ensureKernelShutdown();
        }
        $this->client = static::createClient();

        $this->client->request('GET', $this->urlTo('organization_overview', ['organization' => 'public']));

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testPackagesAllowedForAnonymousUser(): void
    {
        $otherId = $this->fixtures->createAdmin('cto@buddy.works', 'strong');
        $organizationId = $this->fixtures->createOrganization('public', $otherId);

        $this->fixtures->enableAnonymousUserAccess($organizationId);

        if (static::$booted) {
            self::ensureKernelShutdown();
        }
        $this->client = static::createClient();

        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'public']));

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testTokensNotAllowedForAnonymousUser(): void
    {
        $otherId = $this->fixtures->createAdmin('cto@buddy.works', 'strong');
        $organizationId = $this->fixtures->createOrganization('public', $otherId);

        $this->fixtures->enableAnonymousUserAccess($organizationId);

        if (static::$booted) {
            self::ensureKernelShutdown();
        }
        $this->client = static::createClient();
        $this->client->request('GET', $this->urlTo('organization_tokens', ['organization' => 'public']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
    }

    public function testNonExistingOrganizationForAnonymousUser(): void
    {
        if (static::$booted) {
            self::ensureKernelShutdown();
        }
        $this->client = static::createClient();
        $this->client->request('GET', $this->urlTo('organization_overview', ['organization' => 'non-existing']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
    }

    public function testPublicOrganizationOverviewAllowedForAnotherUser(): void
    {
        $otherId = $this->fixtures->createAdmin('cto@buddy.works', 'strong');
        $organizationId = $this->fixtures->createOrganization('public', $otherId);

        $this->fixtures->enableAnonymousUserAccess($organizationId);
        $this->client->request('GET', $this->urlTo('organization_overview', ['organization' => 'public']));

        self::assertTrue($this->client->getResponse()->isOk());
    }
}
