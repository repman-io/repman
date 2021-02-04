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
            "providers-lazy-url": "/p/%package%",
            "provider-includes": {
                "p/provider-latest$%hash%.json": {
                    "sha256": "bf7274d469c9a2c4b4d0babeeb112b40a3afd19a9887adb342671818360ae326"
                }
            }
        }
        ', $this->client->getResponse()->getContent());
    }

    public function testPackagesActionMissingProvider(): void
    {
        $providerBasePath = __DIR__.'/../../Resources/packagist.org/provider';
        $oldProviderName = $providerBasePath.'/provider-latest$bf7274d469c9a2c4b4d0babeeb112b40a3afd19a9887adb342671818360ae326.json';
        $newProviderName = $providerBasePath.'/file.json';

        rename($oldProviderName, $newProviderName);

        $this->client->request('GET', '/packages.json', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]);

        rename($newProviderName, $oldProviderName);

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
            "providers-lazy-url": "/p/%package%",
            "provider-includes": []
        }
        ', $this->client->getResponse()->getContent());
    }

    public function testProviderLazyAction(): void
    {
        $response = $this->contentFromStream(fn () => $this->client->request('GET', '/p/buddy-works/repman', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]));

        self::assertMatchesPattern('
        {
            "packages":
            {
                "buddy-works/repman": "@array@"
            }
        }
        ', $response);
        self::assertTrue($this->client->getResponse()->isCacheable());
    }

    public function testProviderLazyActionEmptyPackagesWhenNotExist(): void
    {
        $response = $this->contentFromStream(fn () => $this->client->request('GET', '/p/buddy-works/example-app', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]));

        self::assertMatchesPattern('
        {
            "packages": {}
        }
        ', $response);
    }

    public function testProviderV2Action(): void
    {
        $response = $this->contentFromStream(fn () => $this->client->request('GET', '/p2/buddy-works/repman.json', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]));

        self::assertMatchesPattern('
        {
            "packages":
            {
                "buddy-works/repman": "@array@"
            }
        }
        ', $response);
        self::assertTrue($this->client->getResponse()->isCacheable());
    }

    public function testProviderV2ActionWhenPackageNotExist(): void
    {
        $this->client->request('GET', '/p2/buddy-works/example-app.json', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]);
        self::assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testProviderAction(): void
    {
        $response = $this->contentFromStream(fn () => $this->client->request('GET', '/p/buddy-works/repman$d5d2c9708c1240da3913ee9fba51759b14b8443826a93b84fa0fa95d70cd3703.json', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]));

        self::assertMatchesPattern('
        {
            "packages":
            {
                "buddy-works/repman": "@array@"
            }
        }
        ', $response);
        self::assertTrue($this->client->getResponse()->isCacheable());
    }

    public function testProviderNotFoundWhenNotExist(): void
    {
        $this->contentFromStream(fn () => $this->client->request('GET', '/p/buddy-works/repman$ee203d24e9722116c133153095cd65f7d94d8261bed4bd77da698dda07e8c98d.json', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]));

        self::assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testProvidersAction(): void
    {
        $response = $this->contentFromStream(fn () => $this->client->request('GET', '/p/provider-latest$bf7274d469c9a2c4b4d0babeeb112b40a3afd19a9887adb342671818360ae326.json', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]));

        self::assertMatchesPattern('
        {
            "providers": {
                "buddy-works/repman": {
                    "sha256": "d5d2c9708c1240da3913ee9fba51759b14b8443826a93b84fa0fa95d70cd3703"
                }
            }
        }
        ', $response);
        self::assertTrue($this->client->getResponse()->isCacheable());
    }

    public function testProvidersNotFoundWhenNotExist(): void
    {
        $this->contentFromStream(fn () => $this->client->request('GET', 'provider-latest$e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855.json', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]));

        self::assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testDistributionAction(): void
    {
        $file = $this->contentFromStream(fn () => $this->client->request('GET', '/dists/buddy-works/repman/0.1.2.0/f0c896a759d4e2e1eff57978318e841911796305.zip', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertTrue($this->client->getResponse()->isCacheable());
    }

    public function testDistributionNotFoundAction(): void
    {
        $this->client->request('GET', '/dists/buddy-works/repman/2.0.0.0/0f1a178ca9c0271bca6426dde8f5a2241578deae.zip', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ]);

        self::assertTrue($this->client->getResponse()->isNotFound());
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

    public function testTrackDownloadsInvalidRequest(): void
    {
        $this->client->request('POST', '/downloads', [], [], [
            'HTTP_HOST' => 'repo.repman.wip',
        ], 'invalid');

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }
}
