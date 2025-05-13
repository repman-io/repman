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
use Buddy\Repman\Query\User\PackageQuery\Filter;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Service\Integration\BitbucketApi;
use Buddy\Repman\Service\Integration\GitHubApi;
use Buddy\Repman\Service\Organization\TokenGenerator;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use DateInterval;
use DateTimeImmutable;
use Generator;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_create'));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Create a new organization', $this->lastResponseBody());

        $this->client->submitForm('Create a new organization', ['name' => 'Acme Inc.']);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_overview', ['organization' => 'acme-inc'])));

        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Organization &quot;Acme Inc.&quot; has been created', $this->lastResponseBody());
    }

    public function testNameCantBeEmpty(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_create'));

        $this->client->followRedirects();
        $this->client->submitForm('Create a new organization', ['name' => '']);

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('This value should not be blank', $this->lastResponseBody());
    }

    public function testInvalidName(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_create'));

        $this->client->followRedirects();
        $this->client->submitForm('Create a new organization', ['name' => '!@#']); // only special chars

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Name cannot consist of special characters only.', $this->lastResponseBody());
    }

    public function testUniqueness(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_create'));
        $this->client->followRedirects();
        $this->client->submitForm('Create a new organization', ['name' => 'same']);

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_create'));
        $this->client->submitForm('Create a new organization', ['name' => 'same']);

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Organization &quot;same&quot; already exists', $this->lastResponseBody());
    }

    public function testOverview(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_overview', ['organization' => 'buddy']));

        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testOverviewNotAllowedForNotOwnedOrganization(): void
    {
        $otherId = $this->fixtures->createAdmin('cto@buddy.works', 'strong');
        $this->fixtures->createOrganization('buddy', $otherId);
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_overview', ['organization' => 'buddy']));

        $this->assertTrue($this->client->getResponse()->isForbidden());
    }

    public function testPackageList(): void
    {
        $anotherUserID = $this->fixtures->createUser('another@user.com', 'secret');

        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $anotherOrgId = $this->fixtures->createOrganization('google', $anotherUserID);

        $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->addPackage($anotherOrgId, 'https://google.com');

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy']));

        $this->assertTrue($this->client->getResponse()->isOk());

        $this->assertStringContainsString('1 entries', (string) $this->client->getResponse()->getContent());
    }

    public function testPackageSearch(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);

        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/testing', '1', '1.1.1', new DateTimeImmutable());

        $packageId2 = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId2, 'buddy-works/example', '2', '1.1.1', new DateTimeImmutable());

        // Check both packages are returned first
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy']));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('2 entries', (string) $this->client->getResponse()->getContent());

        // Search for 'testing' (which is in name)
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'search' => 'testing']));

        $this->assertTrue($this->client->getResponse()->isOk());
        $response = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('1 entries', $response);
        $this->assertStringContainsString($packageId, $response);
        $this->assertStringNotContainsString($packageId2, $response);

        // Search for '2' (which is in description)
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'search' => '2']));

        $this->assertTrue($this->client->getResponse()->isOk());
        $response = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('1 entries', $response);
        $this->assertStringContainsString($packageId2, $response);
        $this->assertStringNotContainsString($packageId, $response);

        // Test serach query params passing
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'search' => 'buddy', 'limit' => 1]));
        $this->assertTrue($this->client->getResponse()->isOk());
        $response = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('2 entries', $response);
        $this->assertStringContainsString('search=buddy', $response);
    }

    public function testDependantSearch(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);

        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/testing', '1', '1.1.1', new DateTimeImmutable());

        $packageId2 = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $links = [
            new Link('requires', 'buddy-works/testing', '^1.5'),
        ];
        $this->fixtures->syncPackageWithData($packageId2, 'buddy-works/example', '2', '1.1.1', new DateTimeImmutable(), [], $links);

        // Search for 'testing' (which is in name)
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'search' => 'depends:buddy-works/testing']));

        $this->assertTrue($this->client->getResponse()->isOk());
        $response = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('1 entries', $response);
        $this->assertStringNotContainsString($packageId, $response);
        $this->assertStringContainsString($packageId2, $response);
    }

    public function testPagination(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);

        for ($i = 0; $i < 111; ++$i) {
            $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        }

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Showing 1 to 1 of 111 entries', $content);
        $this->assertStringContainsString('offset=111&amp;limit=1', $content);

        // Invalid limit (too low)
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => -1]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Showing 1 to 20 of 111 entries', $content);
        $this->assertStringContainsString('offset=100&amp;limit=20', $content);

        // Invalid limit (too high)
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 101]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Showing 1 to 100 of 111 entries', $content);
        $this->assertStringContainsString('offset=100&amp;limit=100', $content);

        // Negative offset
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'offset' => -1]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Showing 1 to 20 of 111 entries', $content);
        $this->assertStringContainsString('offset=0&amp;limit=20', $content);
    }

    public function testSorting(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);

        for ($i = 1; $i < 6; ++$i) {
            $submissionTime = (new DateTimeImmutable())->add(new DateInterval(sprintf('P%dD', $i)));

            $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
            $this->fixtures->syncPackageWithData($packageId, 'buddy-works/package-'.$i, 'Test', '1.'.$i, $submissionTime);
        }

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('buddy-works/package-1', $content);
        $this->assertStringContainsString('sort=name:desc', $content);
        $this->assertStringContainsString('sort=version:asc', $content);
        $this->assertStringContainsString('sort=date:asc', $content);

        // Sort by name desc
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1, 'sort' => 'name:desc']));

        $this->assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('buddy-works/package-5', $content);
        $this->assertStringContainsString('sort=name:asc', $content);
        $this->assertStringContainsString('sort=version:asc', $content);
        $this->assertStringContainsString('sort=date:asc', $content);

        // Sort by version desc
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1, 'sort' => 'version:desc']));

        $this->assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('buddy-works/package-5', $content);
        $this->assertStringContainsString('sort=name:asc', $content);
        $this->assertStringContainsString('sort=version:desc', $content);
        $this->assertStringContainsString('sort=date:asc', $content);

        // Sort by released date asc
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1, 'sort' => 'date:asc']));

        $this->assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('buddy-works/package-1', $content);
        $this->assertStringContainsString('sort=name:asc', $content);
        $this->assertStringContainsString('sort=version:asc', $content);
        $this->assertStringContainsString('sort=date:desc', $content);

        // Sort by invalid column
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy', 'limit' => 1, 'sort' => 'invalid-column:asc']));

        $this->assertTrue($this->client->getResponse()->isOk());
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('buddy-works/package-1', $content);
        $this->assertStringContainsString('sort=name:asc', $content);
        $this->assertStringContainsString('sort=version:asc', $content);
        $this->assertStringContainsString('sort=date:asc', $content);
    }

    public function testRemovePackage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new DateTimeImmutable());

        $this->client->followRedirects(true);
        $this->client->request(Request::METHOD_DELETE, $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertStringContainsString('Package has been successfully removed', $this->lastResponseBody());

        $this->fixtures->prepareRepoFiles();
    }

    public function testRemoveBitbucketPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_BITBUCKET);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'bitbucket-oauth', [Metadata::BITBUCKET_REPO_NAME => 'some/repo']);
        $this->fixtures->setWebhookCreated($packageId);

        $this->client->followRedirects();
        $this->client->request(Request::METHOD_DELETE, $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertStringContainsString('Package has been successfully removed', $this->lastResponseBody());
    }

    public function testRemoveGitHubPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'some/repo']);
        $this->fixtures->setWebhookCreated($packageId);

        $this->client->disableReboot();
        $this->client->followRedirects();
        $this->client->request(Request::METHOD_DELETE, $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertStringContainsString('Package has been successfully removed', $this->lastResponseBody());
        $this->assertSame(['some/repo'], $this->container()->get(GitHubApi::class)->removedWebhooks());
    }

    public function testRemoveGitHubPackageAndIgnoreWebhookError(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'some/repo']);
        $this->fixtures->setWebhookCreated($packageId);
        $this->container()->get(GitHubApi::class)->setExceptionOnNextCall(new RuntimeException('Bad credentials'));

        $this->client->followRedirects();
        $this->client->request(Request::METHOD_DELETE, $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertStringContainsString('Package has been successfully removed', $this->lastResponseBody());
        $this->assertStringContainsString('Webhook removal failed due to', $this->lastResponseBody());
    }

    public function testRemoveGitLabPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'gitlab-oauth', [Metadata::GITLAB_PROJECT_ID => 123]);
        $this->fixtures->setWebhookCreated($packageId);

        $this->client->followRedirects();
        $this->client->request(Request::METHOD_DELETE, $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertStringContainsString('Package has been successfully removed', $this->lastResponseBody());
    }

    public function testSynchronizeWebhookFromGitHubPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'some/repo']);

        $this->client->followRedirects();
        $this->client->request(Request::METHOD_POST, $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertStringContainsString('will be synchronized in background', $this->lastResponseBody());
    }

    public function testSynchronizeWebhookFromGitLabPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'gitlab-oauth', [Metadata::GITLAB_PROJECT_ID => 123]);

        $this->client->followRedirects();
        $this->client->request(Request::METHOD_POST, $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertStringContainsString('will be synchronized in background', $this->lastResponseBody());
    }

    public function testSynchronizeWebhookFromBitbucketPackage(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com', 'bitbucket-oauth', [Metadata::BITBUCKET_REPO_NAME => 'some/repo']);

        $this->client->followRedirects();
        $this->client->request(Request::METHOD_POST, $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertStringContainsString('will be synchronized in background', $this->lastResponseBody());
    }

    public function testUpdateNonExistingPackage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');

        $this->client->request(Request::METHOD_POST, $this->urlTo('organization_package_update', [
            'organization' => 'buddy',
            'package' => Uuid::uuid4()->toString(), // random
        ]));

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testSynchronizationError(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithError($packageId, 'Connection error: 503 service unavailable');

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'buddy']));

        $this->assertStringContainsString('Synchronization error', $this->lastResponseBody());
        $this->assertStringContainsString('Connection error: 503 service unavailable', $this->lastResponseBody());
    }

    public function testRemoveNonExistingPackage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');

        $this->client->request(Request::METHOD_DELETE, $this->urlTo('organization_package_remove', [
            'organization' => 'buddy',
            'package' => Uuid::uuid4()->toString(), // random
        ]));

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testRemoveNotOwnedPackage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $buddyPackageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $repmanId = $this->fixtures->createOrganization('repman', $this->userId);
        $repmanPackageId = $this->fixtures->addPackage($repmanId, 'https://repman.io');

        $this->client->request(Request::METHOD_DELETE, $this->urlTo('organization_package_remove', [
            'organization' => 'repman',
            'package' => $buddyPackageId, // package from other organization
        ]));

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testPackageDetails(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $versions = [
            new Version(Uuid::uuid4(), '1.0.0', 'someref', 1234, new DateTimeImmutable(), Version::STABILITY_STABLE),
            new Version(Uuid::uuid4(), '1.0.1', 'ref2', 1048576, new DateTimeImmutable(), Version::STABILITY_STABLE),
            new Version(Uuid::uuid4(), '1.1.0', 'lastref', 1073741824, new DateTimeImmutable(), Version::STABILITY_STABLE),
        ];
        $links = [
            new Link('requires', 'buddy-works/target', '^1.5'),
            new Link('suggests', 'buddy-works/buddy', '^2.0'), // Suggest self to test dependant link
        ];
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new DateTimeImmutable(), $versions, $links, 'This is a readme');
        $this->fixtures->addScanResult($packageId, 'ok');

        $crawler = $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_details', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('buddy-works/buddy details', $this->lastResponseBody());
        $this->assertStringContainsString('Test', $this->lastResponseBody());
        $this->assertStringContainsString('Available versions', $this->lastResponseBody());
        foreach ($versions as $version) {
            $this->assertStringContainsString($version->version(), $this->lastResponseBody());
            $this->assertStringContainsString($version->reference(), $this->lastResponseBody());
        }

        $crawlerText = $crawler->text(null, true);

        $this->assertStringContainsString('Requirements', $this->lastResponseBody());
        foreach ($links as $link) {
            $this->assertStringContainsString(sprintf('%s: %s', $link->target(), $link->constraint()), $crawlerText);
        }

        $this->assertStringContainsString('Dependant Packages 1', $crawlerText);
        $this->assertStringContainsString('depends:buddy-works/buddy', $this->lastResponseBody());

        $this->assertStringContainsString('This is a readme', $this->lastResponseBody());
        $this->assertStringNotContainsString('This package is <b>abandoned</b>', $this->lastResponseBody());

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_details', [
            'organization' => 'buddy',
            'package' => v4(),
        ]));

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    /**
     * @dataProvider getAbandonedReplacements
     */
    public function testPackageDetailsAbandoned(string $replacementPackage, string $expectedMessage): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new DateTimeImmutable(), [], [], null, $replacementPackage);

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_details', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString($expectedMessage, $this->lastResponseBody());
    }

    /**
     * @return Generator<array<mixed>>
     */
    public function getAbandonedReplacements(): Generator
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

        $crawler = $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_stats', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Total installs: 3', $crawler->text(null, true));

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_version_stats', [
            'organization' => 'buddy',
            'package' => $packageId,
            'version' => $version,
        ]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('{"x":"'.date('Y-m-d').'","y":3}', $this->lastResponseBody());
    }

    public function testPackageWebhookPage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'buddy/works']);

        $this->client->request(Request::METHOD_POST, '/hook/'.$packageId);
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString($this->urlTo('package_webhook', ['package' => $packageId]), $this->lastResponseBody());
        // last requests table is visible
        $this->assertStringContainsString('User agent', $this->lastResponseBody());

        $this->fixtures->setWebhookError($packageId, 'Repository was archived so is read-only.');

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_webhook', [
            'organization' => 'buddy',
            'package' => $packageId,
        ]));
        $this->assertStringContainsString('Repository was archived so is read-only.', $this->lastResponseBody());
    }

    public function testOrganizationStats(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->addPackageDownload(3, $packageId);

        $crawler = $this->client->request(Request::METHOD_GET, $this->urlTo('organizations_stats', [
            'organization' => 'buddy',
        ]));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Total installs: 3', $crawler->text(null, true));
    }

    public function testGenerateNewToken(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_token_new', ['organization' => 'buddy']));
        $this->client->submitForm('Generate', [
            'name' => 'Production Token',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_tokens', ['organization' => 'buddy'])));

        $this->client->followRedirect();
        $this->assertStringContainsString('Production Token', $this->lastResponseBody());
    }

    public function testRegenerateToken(): void
    {
        $this->fixtures->createToken(
            $this->fixtures->createOrganization('buddy', $this->userId),
            'secret-token'
        );
        $this->container()->get(TokenGenerator::class)->setNextToken('regenerated-token');
        $this->client->request(Request::METHOD_POST, $this->urlTo('organization_token_regenerate', [
            'organization' => 'buddy',
            'token' => 'secret-token',
        ]));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_tokens', ['organization' => 'buddy'])));
        $this->client->followRedirect();
        $this->assertStringContainsString('regenerated-token', $this->lastResponseBody());
    }

    public function testRemoveToken(): void
    {
        $this->fixtures->createToken(
            $this->fixtures->createOrganization('buddy', $this->userId),
            'secret-token'
        );
        $this->client->request(Request::METHOD_DELETE, $this->urlTo('organization_token_remove', [
            'organization' => 'buddy',
            'token' => 'secret-token',
        ]));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_tokens', ['organization' => 'buddy'])));
        $this->client->followRedirect();
        $this->assertStringNotContainsString('secret-token', $this->lastResponseBody());
    }

    public function testChangeName(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->followRedirects();
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_settings', ['organization' => 'buddy']));
        $this->client->submitForm('Rename', [
            'name' => 'Meat',
        ]);

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Meat', $this->lastResponseBody());
        $this->assertStringContainsString('Organization name been successfully changed.', $this->lastResponseBody());
    }

    public function testChangeAlias(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->followRedirects();
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_settings', ['organization' => 'buddy']));
        $this->client->submitForm('Change', [
            'alias' => 'repman',
        ]);

        $organization = $this
            ->container()
            ->get(OrganizationRepository::class)
            ->getById(Uuid::fromString($organizationId));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Organization alias has been successfully changed.', $this->lastResponseBody());
        $this->assertSame('repman', $organization->alias());
    }

    public function testChangeAliasWithInvalidChars(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->followRedirects();
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_settings', ['organization' => 'buddy']));
        $this->client->submitForm('Change', [
            'alias' => 'https://repman',
        ]);

        $this->assertStringContainsString('Alias can contain only alphanumeric characters and _ or - sign', $this->lastResponseBody());
        $this->assertStringNotContainsString('Organization alias has been successfully changed.', $this->lastResponseBody());
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

        $this->assertFalse($organization->hasAnonymousAccess());

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_settings', ['organization' => 'buddy']));
        $this->client->submitForm('changeAnonymousAccess', [
            'hasAnonymousAccess' => true,
        ]);

        $organization = $this
            ->container()
            ->get(DbalOrganizationQuery::class)
            ->getByAlias('buddy')
            ->get();

        $this->assertTrue($organization->hasAnonymousAccess());
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Anonymous access has been successfully changed.', $this->lastResponseBody());
    }

    public function testRemoveOrganization(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy inc', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_BITBUCKET);

        $packageId = $this->fixtures->addPackage($organizationId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new DateTimeImmutable());
        $this->fixtures->setWebhookCreated($this->fixtures->addPackage($organizationId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'org/repo']));
        $this->fixtures->setWebhookCreated($this->fixtures->addPackage($organizationId, 'https://buddy.com', 'bitbucket-oauth', [Metadata::BITBUCKET_REPO_NAME => 'webhook/problem']));
        $this->container()->get(BitbucketApi::class)->setExceptionOnNextCall(new RuntimeException('Repository was archived'));

        $this->client->request(Request::METHOD_DELETE, $this->urlTo('organization_remove', [
            'organization' => 'buddy-inc',
        ]));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->disableReboot();
        $this->client->followRedirect();

        $this->assertStringContainsString('Organization buddy inc has been successfully removed', $this->lastResponseBody());
        $this->assertStringContainsString('Repository was archived', $this->lastResponseBody());
        $this->assertSame(0, $this->container()->get(PackageQuery::class)->count($organizationId, new Filter()));
        $this->assertSame(['org/repo'], $this->container()->get(GitHubApi::class)->removedWebhooks());
        $this->assertSame([], $this->container()->get(BitbucketApi::class)->removedWebhooks());
    }

    public function testRemoveForbiddenOrganization(): void
    {
        $otherId = $this->fixtures->createAdmin('cto@buddy.works', 'strong');
        $this->fixtures->createOrganization('buddy', $otherId);

        $this->client->request(Request::METHOD_DELETE, $this->urlTo('organization_remove', [
            'organization' => 'buddy',
        ]));

        $this->assertTrue($this->client->getResponse()->isForbidden());
    }

    public function testPackageEmptyScanResults(): void
    {
        $organization = 'buddy';
        $buddyId = $this->fixtures->createOrganization($organization, $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_scan_results', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        $this->assertStringContainsString('package not scanned yet', $this->lastResponseBody());
    }

    public function testScanPackages(): void
    {
        $organization = 'buddy';
        $buddyId = $this->fixtures->createOrganization($organization, $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $package2Id = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/repman', 'Repository manager', '2.1.1', new DateTimeImmutable('2020-01-01 12:12:12'));
        $this->fixtures->syncPackageWithData($package2Id, 'buddy-works/repman2', 'Repository manager', '2.1.1', new DateTimeImmutable('2020-01-01 12:12:12'));

        $this->client->request(Request::METHOD_POST, $this->urlTo('organization_package_scan', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        $this->assertTrue($this->client->getResponse()->isRedirect(
            $this->urlTo('organization_packages', ['organization' => $organization])
        ));

        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        $this->assertCount(3, $transport->getSent());
        $this->assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());

        $this->fixtures->addScanResult($packageId, 'ok');
        $this->fixtures->addScanResult($package2Id, 'error', [
            'exception' => [
                'RuntimeException' => 'Some error',
            ],
        ]);

        $this->client->followRedirect();
        $this->assertStringContainsString('Package will be scanned in the background', $this->lastResponseBody());
        $this->assertStringContainsString('ok', $this->lastResponseBody());
        $this->assertStringContainsString('no advisories', $this->lastResponseBody());
        $this->assertStringContainsString('error', $this->lastResponseBody());
        $this->assertStringContainsString('&lt;b&gt;RuntimeException&lt;/b&gt; - Some error', $this->lastResponseBody());
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
            new DateTimeImmutable()
        );

        $this->fixtures->addScanResult($packageId, 'ok');

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_scan_results', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        $this->assertStringContainsString($version, $this->lastResponseBody());
        $this->assertStringContainsString('ok', $this->lastResponseBody());
        $this->assertStringContainsString('no advisories', $this->lastResponseBody());
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
            new DateTimeImmutable()
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

        $crawler = $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_scan_results', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        $this->assertStringContainsString($version, $this->lastResponseBody());
        $this->assertStringContainsString('buddy-works/repman security scan results', $crawler->text(null, true));
        $this->assertStringContainsString('warning', $this->lastResponseBody());
        $this->assertStringContainsString('vendor/some-dependency', $this->lastResponseBody());
        $this->assertStringContainsString('6.6.6', $this->lastResponseBody());
        $this->assertStringContainsString('Direct access of ESI URLs behind a trusted proxy', $this->lastResponseBody());
        $this->assertStringContainsString('CVE-2014-5245', $this->lastResponseBody());
        $this->assertStringContainsString('https://symfony.com/cve-2014-5245', $this->lastResponseBody());
        $this->assertStringNotContainsString('sub-dir/composer.lock', $this->lastResponseBody());
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
            new DateTimeImmutable()
        );

        $this->fixtures->addScanResult($packageId, 'error', [
            'exception' => [
                'RuntimeException' => 'Some error',
            ],
        ]);

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_scan_results', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        $this->assertStringContainsString($version, $this->lastResponseBody());
        $this->assertStringContainsString('error', $this->lastResponseBody());
        $this->assertStringContainsString('<b>RuntimeException</b> - Some error', $this->lastResponseBody());
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
            new DateTimeImmutable()
        );

        $this->fixtures->addScanResult($packageId, 'n/a', []);

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_package_scan_results', [
            'organization' => $organization,
            'package' => $packageId,
        ]));

        $this->assertStringContainsString($version, $this->lastResponseBody());
        $this->assertStringContainsString('n/a', $this->lastResponseBody());
        $this->assertStringContainsString('composer.lock not present', $this->lastResponseBody());
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

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_overview', ['organization' => 'public']));

        $this->assertTrue($this->client->getResponse()->isOk());
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

        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_packages', ['organization' => 'public']));

        $this->assertTrue($this->client->getResponse()->isOk());
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
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_tokens', ['organization' => 'public']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
    }

    public function testNonExistingOrganizationForAnonymousUser(): void
    {
        if (static::$booted) {
            self::ensureKernelShutdown();
        }

        $this->client = static::createClient();
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_overview', ['organization' => 'non-existing']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
    }

    public function testPublicOrganizationOverviewAllowedForAnotherUser(): void
    {
        $otherId = $this->fixtures->createAdmin('cto@buddy.works', 'strong');
        $organizationId = $this->fixtures->createOrganization('public', $otherId);

        $this->fixtures->enableAnonymousUserAccess($organizationId);
        $this->client->request(Request::METHOD_GET, $this->urlTo('organization_overview', ['organization' => 'public']));

        $this->assertTrue($this->client->getResponse()->isOk());
    }
}
