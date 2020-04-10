<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class ProxyControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAndLoginAdmin();
    }

    public function testDistList(): void
    {
        $this->client->request('GET', $this->urlTo('admin_dist_list', ['proxy' => 'packagist.org']));

        self::assertStringContainsString('packagist.org', (string) $this->client->getResponse()->getContent());
    }

    public function testStats(): void
    {
        $this->client->request('GET', $this->urlTo('admin_proxy_stats'));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Total installs:', $this->lastResponseBody());
    }

    public function testRemoveDistPackage(): void
    {
        $this->client->request('DELETE', $this->urlTo('admin_dist_remove', ['proxy' => 'packagist.org', 'packageName' => 'vendor/package']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_dist_list', ['proxy' => 'packagist.org'])));
        self::assertTrue($this->container()->get('session')->getFlashBag()->has('success'));
    }
}
