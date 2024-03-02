<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RepoControllerTest extends FunctionalTestCase
{
    public function testAuthRequired(): void
    {
        $this->client->request('GET', '/', [], [], ['HTTP_HOST' => 'buddy.repo.repman.wip']);

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testAuthRequiredForOrganizationRepo(): void
    {
        $this->client->request('GET', '/packages.json', [], [], ['HTTP_HOST' => 'buddy.repo.repman.wip']);

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPackagesActionWithInvalidToken(): void
    {
        $this->client->request('GET', '/packages.json', [], [], [
            'HTTP_HOST' => 'buddy.repo.repman.wip',
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testNotAllowedToSeeOtherOrganizationPackages(): void
    {
        $this->fixtures->createOrganization(
            'evil',
            $this->fixtures->createUser('bad@evil.corp')
        );

        $this->fixtures->createToken(
            $this->fixtures->createOrganization(
                'buddy',
                $this->fixtures->createUser()
            ),
            'secret-org-token'
        );

        $this->client->request('GET', '/packages.json', [], [], [
            'HTTP_HOST' => 'evil.repo.repman.wip',
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testOrganizationPackagesAction(): void
    {
        // create and login admin to check token last used date is changed
        $adminId = $this->createAndLoginAdmin('test@buddy.works', 'secret');
        $organizationId = $this->fixtures->createOrganization('buddy', $adminId);
        $this->fixtures->createToken($organizationId, 'secret-org-token');
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId, 'buddy', $organizationId);
        $this->fixtures->syncPackageWithData($packageId, 'buddy-works/repman', 'Test', '1.1.1', new \DateTimeImmutable());

        // check token was never used
        $this->client->request('GET', $this->urlTo('organization_tokens', ['organization' => 'buddy']));
        self::assertStringContainsString('never', $this->lastResponseBody());

        $this->client->request('GET', '/packages.json', [], [], [
            'HTTP_HOST' => 'buddy.repo.repman.wip',
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertJsonStringEqualsJsonString('
        {
            "packages": {
               "buddy-works\/repman": {
                    "1.2.3": {
                        "version": "1.2.3",
                        "version_normalized": "1.2.3.0",
                        "dist": {
                            "type": "zip",
                            "url": "\/path\/to\/reference.zip",
                            "reference": "ac7dcaf888af2324cd14200769362129c8dd8550"
                        }
                    }
                }
            },
            "available-packages": [
                "buddy-works/repman"
            ],
            "metadata-url": "/p2/%package%.json",
            "notify-batch": "http://buddy.repo.repman.wip/downloads",
            "search": "https://packagist.org/search.json?q=%query%&type=%type%",
            "mirrors": [
                {
                    "dist-url": "http://buddy.repo.repman.wip/dists/%package%/%version%/%reference%.%type%",
                    "preferred": true
                },
                {
                    "dist-url": "http://buddy.repo.repman.wip/dists/%package%/%version%/%type%",
                    "preferred": false
                }
            ]
        }
        ', $this->lastResponseBody());

        // check if last update date is changed
        $this->client->request('GET', $this->urlTo('organization_tokens', ['organization' => 'buddy']));
        self::assertStringNotContainsString('never', $this->lastResponseBody());
    }

    public function testOrganizationPackageDistDownload(): void
    {
        $this->fixtures->prepareRepoFiles();
        $this->fixtures->createToken(
            $this->fixtures->createOrganization('buddy', $this->fixtures->createUser()),
            'secret-org-token'
        );

        $this->contentFromStream(function (): void {
            $this->client->request('GET', '/dists/buddy-works/repman/1.2.3.0/ac7dcaf888af2324cd14200769362129c8dd8550.zip', [], [], [
                'HTTP_HOST' => 'buddy.repo.repman.wip',
                'PHP_AUTH_USER' => 'token',
                'PHP_AUTH_PW' => 'secret-org-token',
            ]);
        });

        $response = $this->client->getResponse();
        self::assertTrue($response->isOk(), 'Response code was not 200, it was instead '.$response->getStatusCode());
        self::assertInstanceOf(StreamedResponse::class, $response);

        $this->contentFromStream(function (): void {
            $this->client->request('GET', '/dists/vendor/package/9.9.9.9/ac7dcaf888af2324cd14200769362129c8dd8550.zip', [], [], [
                'HTTP_HOST' => 'buddy.repo.repman.wip',
                'PHP_AUTH_USER' => 'token',
                'PHP_AUTH_PW' => 'secret-org-token',
            ]);
        });

        self::assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testOrganizationArtifactPackageDistDownload(): void
    {
        $this->fixtures->prepareRepoFiles();
        $this->fixtures->createToken(
            $this->fixtures->createOrganization('buddy', $this->fixtures->createUser()),
            'secret-org-token'
        );

        $this->contentFromStream(function (): void {
            $this->client->request('GET', '/dists/buddy-works/artifact/1.0.0.0/zip', [], [], [
                'HTTP_HOST' => 'buddy.repo.repman.wip',
                'PHP_AUTH_USER' => 'token',
                'PHP_AUTH_PW' => 'secret-org-token',
            ]);
        });

        $response = $this->client->getResponse();
        self::assertTrue($response->isOk(), 'Response code was not 200, it was instead '.$response->getStatusCode());
        self::assertInstanceOf(StreamedResponse::class, $response);

        $this->contentFromStream(function (): void {
            $this->client->request('GET', '/dists/vendor/artifact/1.0.0.0/zip', [], [], [
                'HTTP_HOST' => 'buddy.repo.repman.wip',
                'PHP_AUTH_USER' => 'token',
                'PHP_AUTH_PW' => 'secret-org-token',
            ]);
        });

        self::assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testOrganizationTrackDownloads(): void
    {
        $this->fixtures->createPackage('c75b535f-5817-41a2-9424-e05476e7958f', 'buddy');
        $this->fixtures->syncPackageWithData('c75b535f-5817-41a2-9424-e05476e7958f', 'buddy-works/repman', 'desc', '1.2.0', new \DateTimeImmutable());

        $this->client->request('POST', '/downloads', [], [], [
            'HTTP_HOST' => 'buddy.repo.repman.wip',
        ], \json_encode(
            [
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
            ],
            \JSON_THROW_ON_ERROR
            )
        );

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/downloads', [], [], [
            'HTTP_HOST' => 'buddy.repo.repman.wip',
        ], (string) \json_encode([]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonymousUserAccess(): void
    {
        $organizationId = $this->fixtures->createOrganization('buddy', $this->fixtures->createUser());
        $this->fixtures->enableAnonymousUserAccess($organizationId);

        $this->client->request('GET', '/packages.json', [], [], ['HTTP_HOST' => 'buddy.repo.repman.wip']);

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testProviderV2Action(): void
    {
        $adminId = $this->createAndLoginAdmin('test@buddy.works', 'secret');
        $this->fixtures->createToken($this->fixtures->createOrganization('buddy', $adminId), 'secret-org-token');

        $this->client->request('GET', '/p2/buddy-works/repman.json', [], [], [
            'HTTP_HOST' => 'buddy.repo.repman.wip',
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertTrue($this->client->getResponse()->isOk());

        self::assertMatchesPattern('
        {
            "packages": {
                "buddy-works/repman": {
                    "1.2.3": {
                        "version": "1.2.3",
                        "version_normalized": "1.2.3.0",
                        "dist": {
                            "type": "zip",
                            "url": "/path/to/reference.zip",
                            "reference": "ac7dcaf888af2324cd14200769362129c8dd8550"
                        }
                    }
                }
            }
        }
        ', $this->client->getResponse()->getContent());
    }

    public function testProviderV2ActionWithCache(): void
    {
        $adminId = $this->createAndLoginAdmin('test@buddy.works', 'secret');
        $this->fixtures->createToken($this->fixtures->createOrganization('buddy', $adminId), 'secret-org-token');

        $fileModifiedTime = (new \DateTimeImmutable())
            ->setTimestamp((int) \filemtime(__DIR__.'/../../Resources/p2/buddy-works/repman.json'));

        $this->client->request('GET', '/p2/buddy-works/repman.json', [], [], [
            'HTTP_HOST' => 'buddy.repo.repman.wip',
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
            'HTTP_IF_MODIFIED_SINCE' => $fileModifiedTime->format('D, d M Y H:i:s \G\M\T'),
        ]);

        self::assertEquals(304, $this->client->getResponse()->getStatusCode());
        self::assertEmpty($this->client->getResponse()->getContent());
    }

    public function testProviderV2ForMissingPackage(): void
    {
        $adminId = $this->createAndLoginAdmin('test@buddy.works', 'secret');
        $this->fixtures->createToken($this->fixtures->createOrganization('buddy', $adminId), 'secret-org-token');

        $this->client->request('GET', '/p2/buddy-works/fake.json', [], [], [
            'HTTP_HOST' => 'buddy.repo.repman.wip',
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testProviderV2DevAction(): void
    {
        $adminId = $this->createAndLoginAdmin('test@buddy.works', 'secret');
        $this->fixtures->createToken($this->fixtures->createOrganization('buddy', $adminId), 'secret-org-token');

        $this->client->request('GET', '/p2/buddy-works/repman~dev.json', [], [], [
            'HTTP_HOST' => 'buddy.repo.repman.wip',
            'PHP_AUTH_USER' => 'token',
            'PHP_AUTH_PW' => 'secret-org-token',
        ]);

        self::assertTrue($this->client->getResponse()->isOk());

        self::assertMatchesPattern('
        {
            "packages": {
                "buddy-works/repman": {
                    "1.2.3": {
                        "version": "1.2.3",
                        "version_normalized": "1.2.3.0",
                        "dist": {
                            "type": "zip",
                            "url": "/path/to/reference.zip",
                            "reference": "ac7dcaf888af2324cd14200769362129c8dd8550"
                        }
                    }
                }
            }
        }
        ', $this->client->getResponse()->getContent());
    }
}
