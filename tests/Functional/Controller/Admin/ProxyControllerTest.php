<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Message\Proxy\AddDownloads\Package;
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
        $this->fixtures->addProxyPackageDownload(
            [new Package('buddy-works/repman', '1.0.0.0')],
            new \DateTimeImmutable($time = 'April 27, 2020 19:34')
        );
        $this->client->request('GET', $this->urlTo('admin_dist_list', ['proxy' => 'packagist.org']));

        self::assertStringContainsString('packagist.org', $this->lastResponseBody());
        self::assertStringContainsString($time, $this->lastResponseBody());
    }

    public function testStats(): void
    {
        $this->fixtures->addProxyPackageDownload(
            [new Package('buddy-works/repman', '1.0.0.0')],
            new \DateTimeImmutable('April 27, 2020 19:34')
        );
        $this->client->request('GET', $this->urlTo('admin_proxy_stats'));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Total installs: 1', $this->lastResponseBody());
    }

    public function testRemoveDistPackage(): void
    {
        $this->client->request('DELETE', $this->urlTo('admin_dist_remove', ['proxy' => 'packagist.org', 'packageName' => 'vendor/package']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_dist_list', ['proxy' => 'packagist.org'])));
        self::assertTrue($this->container()->get('session')->getFlashBag()->has('success'));
    }
}
