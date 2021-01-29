<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Organization;

use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Buddy\Repman\Message\Organization\Package\AddGitHubHook;
use Buddy\Repman\Message\Organization\Package\AddGitLabHook;
use Buddy\Repman\Message\Security\ScanPackage;
use Buddy\Repman\Service\Integration\GitHubApi;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Github\Exception\ApiLimitExceedException;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

final class PackageControllerTest extends FunctionalTestCase
{
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createAndLoginAdmin();
    }

    public function testAddPackage(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'git']));

        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->submitForm('Add', [
            'url' => 'http://github.com/test/test',
            'type' => 'git',
        ]);

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_packages', ['organization' => 'buddy']))
        );
        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        self::assertCount(1, $transport->getSent());
        self::assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());

        $this->client->followRedirect();
        self::assertStringContainsString('Packages has been added', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testAddPackageFromPath(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'path']));

        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->submitForm('Add', [
            'url' => '/path/to/package',
            'type' => 'path',
        ]);

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_packages', ['organization' => 'buddy']))
        );
        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        self::assertCount(1, $transport->getSent());
        self::assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());

        $this->client->followRedirect();
        self::assertStringContainsString('Packages has been added', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testHandleErrorWhenFetchingRepositories(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, 'github');
        $this->container()->get(GitHubApi::class)->setExceptionOnNextCall(new ApiLimitExceedException());
        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'github']));

        self::assertStringContainsString('Failed to fetch repositories (reason: You have reached GitHub hourly limit!', (string) $this->client->getResponse()->getContent());
    }

    public function testNewPackageFromGithub(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, 'github');

        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'github']));

        $this->client->submitForm('Add', [
            'repositories' => ['buddy/repman'],
        ]);

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('organization_packages', ['organization' => 'buddy']))
        );

        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        self::assertCount(2, $transport->getSent());
        self::assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());
        self::assertInstanceOf(AddGitHubHook::class, $transport->getSent()[1]->getMessage());

        $this->client->followRedirect();
        self::assertStringContainsString('Packages has been added and will be synchronized in the background', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testNewPackageFromGitHubWithoutToken(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'github']));

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('fetch_github_package_token', ['organization' => 'buddy']))
        );
    }

    public function testNewPackageFromGitLab(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, 'gitlab');

        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'gitlab']));
        $this->client->submitForm('Add', [
            'repositories' => [123456],
        ]);

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('organization_packages', ['organization' => 'buddy']))
        );

        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        self::assertCount(2, $transport->getSent());
        self::assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());
        self::assertInstanceOf(AddGitLabHook::class, $transport->getSent()[1]->getMessage());

        $this->client->followRedirect();
        self::assertStringContainsString('Packages has been added and will be synchronized in the background', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testNewPackageFromGitLabWithoutToken(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'gitlab']));

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('fetch_gitlab_package_token', ['organization' => 'buddy']))
        );
    }

    public function testNewPackageFromBitbucket(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, 'bitbucket');

        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'bitbucket']));
        $this->client->submitForm('Add', [
            'repositories' => ['{0f6dc6fe-f8ab-4a53-bb63-03042b80056f}'],
        ]);

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('organization_packages', ['organization' => 'buddy']))
        );

        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        self::assertCount(2, $transport->getSent());
        self::assertInstanceOf(ScanPackage::class, $transport->getSent()[0]->getMessage());
        self::assertInstanceOf(AddBitbucketHook::class, $transport->getSent()[1]->getMessage());

        $this->client->followRedirect();
        self::assertStringContainsString('Packages has been added and will be synchronized in the background', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testNewPackageFromBitbucketWithoutToken(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'bitbucket']));

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('fetch_bitbucket_package_token', ['organization' => 'buddy']))
        );
    }

    public function testNewPackageUnsupportedType(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'bogus']));

        self::assertEquals(404, $this->client->getResponse()->getStatusCode());
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
        self::assertStringContainsString('Package will be synchronized in the background', $this->lastResponseBody());
        self::assertStringContainsString('buddy-works/repman', $this->lastResponseBody());
        self::assertStringContainsString('2.1.1', $this->lastResponseBody());
    }

    public function testEditPackage(): void
    {
        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/buddy', 'Test', '1.1.1', new \DateTimeImmutable());

        $this->client->request('GET', $this->urlTo('organization_package_edit', ['organization' => 'buddy', 'package' => $packageId]));

        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->submitForm('Update', [
            'url' => 'http://github.com/test/test',
            'keepLastReleases' => '6',
        ]);

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_packages', ['organization' => 'buddy']))
        );

        $this->client->followRedirect();
        self::assertStringContainsString('Package will be synchronized in the background', $this->lastResponseBody());
        self::assertStringContainsString('http://github.com/test/test', $this->lastResponseBody());

        self::assertTrue($this->client->getResponse()->isOk());
    }
}
