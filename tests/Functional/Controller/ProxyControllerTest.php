<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

final class ProxyControllerTest extends FunctionalTestCase
{
    public function testPackagesAction(): void
    {
        $this->client->request('GET', '/packages.json', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]);

        self::assertMatchesPattern('
        {
            "notify-batch": "http://repo.repman.wip/downloads",
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

    public function testTrackDownloads(): void
    {
        $this->client->request('POST', '/downloads', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ], (string) json_encode([
            'downloads' => [
                [
                    'name' => 'buddy-works/repman',
                    'version' => '1.2.0.0',
                ],
                [
                    'name' => 'not-exist',
                    'version' => 'should-not-throw-error',
                ],
                [
                    'name' => 'missing version',
                ],
            ],
        ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        /** @var InMemoryTransport $transport */
        $transport = $this->container()->get('messenger.transport.async');
        self::assertCount(1, $transport->getSent());
    }
}
