<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class DistControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAndLoginAdmin();
    }

    public function testDistList(): void
    {
        $this->client->request('GET', $this->urlTo('admin_dist_list'));

        self::assertStringContainsString('packagist.org', (string) $this->client->getResponse()->getContent());
    }

    public function testRemoveDistPackage(): void
    {
        $this->client->request('DELETE', '/admin/dist/vendor/package');

        self::assertTrue($this->client->getResponse()->isRedirect('/admin/dist'));
        self::assertTrue($this->container()->get('session')->getFlashBag()->has('success'));
    }
}
