<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class ProxyControllerTest extends FunctionalTestCase
{
    public function testPackagesAction(): void
    {
        $this->client->request('GET', '/packages.json', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]);

        self::assertMatchesPattern('
        {
            "notify-batch": "https://packagist.org/downloads/",
            "providers-url": "/p/%package%$%hash%.json",
            "metadata-url": "/p2/%package%.json",
            "search": "https://packagist.org/search.json?q=%query%&type=%type%",
            "mirrors": [
                {
                    "dist-url": "@string@.isUrl()",
                    "preferred": true
                }
            ],
            "providers-lazy-url": "/p/%package%"
        }
        ', $this->client->getResponse()->getContent());
    }

    public function testProviderAction(): void
    {
        $this->client->request('GET', '/p/buddy-works/repman', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]);

        self::assertMatchesPattern('
        {
            "packages":
            {
                "buddy-works/repman": "@array@"
            }
        }
        ', $this->client->getResponse()->getContent());
    }

    public function testProviderActionEmptyPackagesWhenNotExist(): void
    {
        $this->client->request('GET', '/p/buddy-works/example-app', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]);

        self::assertMatchesPattern('
        {
            "packages": {}
        }
        ', $this->client->getResponse()->getContent());
    }

    public function testDistributionAction(): void
    {
        $this->client->request('GET', '/dists/buddy-works/repman/0.1.2.0/f0c896a759d4e2e1eff57978318e841911796305.zip', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]);

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testPackagesListAction(): void
    {
        $this->client->request('GET', '/packages');

        self::assertStringContainsString('packagist.org', (string) $this->client->getResponse()->getContent());
    }
}
