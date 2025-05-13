<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;

final class OrganizationControllerTest extends FunctionalTestCase
{
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = $this->createAndLoginAdmin();
        $this->fixtures->createOrganization('Acme', $this->userId);
    }

    public function testList(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_organization_list'));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Organizations', $this->lastResponseBody());
        $this->assertStringContainsString('Acme', $this->lastResponseBody());
    }

    public function testRemoveOrganization(): void
    {
        $this->client->request(Request::METHOD_DELETE, $this->urlTo('admin_organization_remove', [
            'organization' => 'acme',
        ]));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_organization_list')));
        $this->client->followRedirect();

        $this->assertStringContainsString('Organization Acme has been successfully removed', $this->lastResponseBody());
    }

    public function testAddAdmin(): void
    {
        $this->client->request(Request::METHOD_POST, $this->urlTo('admin_organization_add_admin', [
            'organization' => 'acme',
        ]));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_organization_list')));
        $this->client->followRedirect();

        $this->assertStringContainsString('The user test@buddy.works has been successfully invited for Acme', $this->lastResponseBody());
    }

    public function testStats(): void
    {
        $orgId = $this->fixtures->createOrganization('buddy', $this->userId);
        $packageId = $this->fixtures->addPackage($orgId, 'https://some.url');
        $this->fixtures->addPackageDownload(1, $packageId);
        $crawler = $this->client->request(Request::METHOD_GET, $this->urlTo('admin_stats'));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Total installs: 1', $crawler->text(null, true));
    }
}
