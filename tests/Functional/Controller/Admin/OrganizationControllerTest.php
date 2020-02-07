<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;

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
        $this->client->request('GET', $this->urlTo('admin_organization_list'));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Organizations', $this->lastResponseBody());
        self::assertStringContainsString('Acme', $this->lastResponseBody());
    }
}
