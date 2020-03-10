<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Organization;

use Buddy\Repman\Message\Organization\Package\AddBitbucketHook;
use Buddy\Repman\Message\Organization\Package\AddGitHubHook;
use Buddy\Repman\Message\Organization\Package\AddGitLabHook;
use Buddy\Repman\Message\Organization\SynchronizePackage;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

final class PackageControllerTest extends FunctionalTestCase
{
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createAndLoginAdmin();
        $this->fixtures->createOrganization('buddy', $this->userId);
    }

    public function testNewPackageFromGitLab(): void
    {
        $this->fixtures->createOauthToken($this->userId, 'gitlab');

        $this->client->request('GET', $this->urlTo('organization_package_new_from_gitlab', ['organization' => 'buddy']));
        $this->client->submitForm('Import', [
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
        self::assertInstanceOf(SynchronizePackage::class, $transport->getSent()[0]->getMessage());
        self::assertInstanceOf(AddGitLabHook::class, $transport->getSent()[1]->getMessage());

        $this->client->followRedirect();
        self::assertStringContainsString('Packages has been added and will be synchronized in the background', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testNewPackageFromGitLabWithoutToken(): void
    {
        $this->client->request('GET', $this->urlTo('organization_package_new_from_gitlab', ['organization' => 'buddy']));

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('fetch_gitlab_package_token', ['organization' => 'buddy']))
        );
    }

    public function testNewPackageFromGithub(): void
    {
        $this->fixtures->createOauthToken($this->userId, 'github');

        $this->client->request('GET', $this->urlTo('organization_package_new_from_github', ['organization' => 'buddy']));
        $this->client->submitForm('Import', [
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
        self::assertInstanceOf(SynchronizePackage::class, $transport->getSent()[0]->getMessage());
        self::assertInstanceOf(AddGitHubHook::class, $transport->getSent()[1]->getMessage());

        $this->client->followRedirect();
        self::assertStringContainsString('Packages has been added and will be synchronized in the background', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testNewPackageFromGitHubWithoutToken(): void
    {
        $this->client->request('GET', $this->urlTo('organization_package_new_from_github', ['organization' => 'buddy']));

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('fetch_github_package_token', ['organization' => 'buddy']))
        );
    }

    public function testNewPackageFromBitbucket(): void
    {
        $this->fixtures->createOauthToken($this->userId, 'bitbucket');

        $this->client->request('GET', $this->urlTo('organization_package_new_from_bitbucket', ['organization' => 'buddy']));
        $this->client->submitForm('Import', [
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
        self::assertInstanceOf(SynchronizePackage::class, $transport->getSent()[0]->getMessage());
        self::assertInstanceOf(AddBitbucketHook::class, $transport->getSent()[1]->getMessage());

        $this->client->followRedirect();
        self::assertStringContainsString('Packages has been added and will be synchronized in the background', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testNewPackageFromBitbucketWithoutToken(): void
    {
        $this->client->request('GET', $this->urlTo('organization_package_new_from_bitbucket', ['organization' => 'buddy']));

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('fetch_bitbucket_package_token', ['organization' => 'buddy']))
        );
    }
}
