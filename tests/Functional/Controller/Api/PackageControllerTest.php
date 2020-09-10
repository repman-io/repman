<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Api;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Query\User\PackageQuery\DbalPackageQuery;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PackageControllerTest extends FunctionalTestCase
{
    private string $apiToken;
    private string $organizationId;
    private string $userId;
    private static string $organization = 'buddy';
    private static string $fakeId = '23b7b63c-a2c3-43f9-a4e6-ab74ba60ef11';

    protected function setUp(): void
    {
        parent::setUp();

        $email = 'api@buddy.works';
        $this->userId = $this->fixtures->createUser($email);
        $this->organizationId = $this->fixtures->createOrganization(self::$organization, $this->userId);

        $this->apiToken = 'test-api-token';
        $this->fixtures->createApiToken($this->userId, $this->apiToken);
    }

    public function testAuthorizationRequired(): void
    {
        $this->client->request('GET', $this->urlTo('api_packages', ['organization' => self::$organization]));

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "credentials": [
                        "Authentication required."
                    ]
                }
            }
            '
        );
    }

    public function testInvalidCredentials(): void
    {
        $this->loginApiUser('fake-token');
        $this->client->request('GET', $this->urlTo('api_packages', ['organization' => self::$organization]));

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "credentials": [
                        "Invalid credentials."
                    ]
                }
            }
            '
        );
    }

    public function testAccessDenied(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('GET', $this->urlTo('api_packages', ['organization' => self::$fakeId]));

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPackagesList(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId, '', $this->organizationId);

        $this->loginApiUser($this->apiToken);
        $this->client->request('GET', $this->urlTo('api_packages', ['organization' => self::$organization]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "data": [
                    {
                        "id": "'.$packageId.'",
                        "organizationId": "'.$this->organizationId.'",
                        "type": "vcs",
                        "url": "https://github.com/buddy-works/repman",
                        "name": null,
                        "latestReleasedVersion": null,
                        "latestReleaseDate": null,
                        "description": null,
                        "lastSyncAt": null,
                        "lastSyncError": null,
                        "webhookCreatedAt": null,
                        "scanResult": null
                    }
                ],
                "total": 1,
                "links": {
                    "first": "http://localhost/api/buddy/package?page=1",
                    "last": "http://localhost/api/buddy/package?page=1",
                    "next": null,
                    "prev": null
                }
            }
            '
        );
    }

    public function testPackagesListPagination(): void
    {
        $baseId = '23b7b63c-a2c3-43f9-a4e6-ab74ba60ef';
        for ($i = 1; $i <= 41; ++$i) {
            $this->fixtures->createPackage($baseId.str_pad((string) $i, 2, '0', STR_PAD_LEFT), '', $this->organizationId);
        }

        $this->loginApiUser($this->apiToken);
        $this->client->request('GET', $this->urlTo('api_packages', [
            'organization' => self::$organization,
            'page' => 2,
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $json = $this->jsonResponse();

        self::assertEquals(count($json['data']), 20);
        self::assertEquals($json['total'], 41);

        $baseUrl = $this->urlTo('api_packages', ['organization' => self::$organization], UrlGeneratorInterface::ABSOLUTE_URL);
        self::assertEquals($baseUrl.'?page=1', $json['links']['first']);
        self::assertEquals($baseUrl.'?page=1', $json['links']['prev']);
        self::assertEquals($baseUrl.'?page=3', $json['links']['next']);
        self::assertEquals($baseUrl.'?page=3', $json['links']['last']);
    }

    public function testEmptyPackagesList(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('GET', $this->urlTo('api_packages', ['organization' => self::$organization]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $json = $this->jsonResponse();
        self::assertEquals($json['data'], []);
        self::assertEquals($json['total'], 0);

        $baseUrl = $this->urlTo('api_packages', ['organization' => self::$organization], UrlGeneratorInterface::ABSOLUTE_URL);
        self::assertEquals($baseUrl.'?page=1', $json['links']['first']);
        self::assertEquals(null, $json['links']['prev']);
        self::assertEquals(null, $json['links']['next']);
        self::assertEquals($baseUrl.'?page=1', $json['links']['last']);
    }

    public function testFindPackage(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId, '', $this->organizationId);

        $this->loginApiUser($this->apiToken);
        $this->client->request('GET', $this->urlTo('api_package_get', [
            'organization' => self::$organization,
            'package' => $packageId,
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "id": "'.$packageId.'",
                "organizationId": "'.$this->organizationId.'",
                "type": "vcs",
                "url": "https://github.com/buddy-works/repman",
                "name": null,
                "latestReleasedVersion": null,
                "latestReleaseDate": null,
                "description": null,
                "lastSyncAt": null,
                "lastSyncError": null,
                "webhookCreatedAt": null,
                "scanResult": null
            }
            '
        );
    }

    public function testFindPackageNonExisting(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('GET', $this->urlTo('api_package_get', [
            'organization' => self::$organization,
            'package' => self::$fakeId,
        ]));

        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testRemovePackage(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId, '', $this->organizationId);

        $this->loginApiUser($this->apiToken);
        $this->client->request('DELETE', $this->urlTo('api_package_remove', [
            'organization' => self::$organization,
            'package' => $packageId,
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertTrue(
            $this->container()
                ->get(DbalPackageQuery::class)
                ->getById($packageId)
                ->isEmpty()
        );
    }

    public function testRemovePackageNonExisting(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('DELETE', $this->urlTo('api_package_remove', [
            'organization' => self::$organization,
            'package' => self::$fakeId,
        ]));

        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdatePackage(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId, '', $this->organizationId);

        $this->loginApiUser($this->apiToken);
        $this->client->request('PUT', $this->urlTo('api_package_update', [
            'organization' => self::$organization,
            'package' => $packageId,
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertFalse(
            $this->container()
                ->get(DbalPackageQuery::class)
                ->getById($packageId)
                ->isEmpty()
        );
    }

    public function testUpdatePackageNonExisting(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('PUT', $this->urlTo('api_package_update', [
            'organization' => self::$organization,
            'package' => self::$fakeId,
        ]));

        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testAddPackageByUrl(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => 'git',
            'url' => 'https://github.com/buddy/test-composer-package',
        ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertFalse(
            $this->container()
                ->get(DbalPackageQuery::class)
                ->getById($this->jsonResponse()['id'])
                ->isEmpty()
        );
    }

    public function testAddPackageByPath(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => 'path',
            'url' => '/path/to/package',
        ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertFalse(
            $this->container()
                ->get(DbalPackageQuery::class)
                ->getById($this->jsonResponse()['id'])
                ->isEmpty()
        );
    }

    public function testAddPackageFromGitHub(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_GITHUB,
            'repository' => 'buddy-works/repman',
        ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertFalse(
            $this->container()
                ->get(DbalPackageQuery::class)
                ->getById($this->jsonResponse()['id'])
                ->isEmpty()
        );
    }

    public function testAddPackageMissingGitHubRepoName(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_GITHUB,
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "repository": [
                        "This value should not be blank."
                    ]
                }
            }
            '
        );
    }

    public function testAddPackageMissingGitHubIntegration(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_GITHUB,
            'repository' => 'buddy-works/repman',
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "type": [
                        "Missing github integration."
                    ]
                }
            }
            '
        );
    }

    public function testAddPackageFromGitLab(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_GITLAB,
            'repository' => 'buddy-works/repman',
        ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertFalse(
            $this->container()
                ->get(DbalPackageQuery::class)
                ->getById($this->jsonResponse()['id'])
                ->isEmpty()
        );
    }

    public function testAddPackageFromGitLabRepoNotFound(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_GITLAB,
            'repository' => 'buddy-works/missing',
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "repository": [
                        "Repository \'buddy-works/missing\' not found."
                    ]
                }
            }
            '
        );
    }

    public function testAddPackageMissingGitLabRepoName(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_GITLAB,
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "repository": [
                        "This value should not be blank."
                    ]
                }
            }
            '
        );
    }

    public function testAddPackageMissingGitLabIntegration(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_GITLAB,
            'repository' => 'buddy-works/repman',
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "type": [
                        "Missing gitlab integration."
                    ]
                }
            }
            '
        );
    }

    public function testAddPackageFromBitbucket(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_BITBUCKET);
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_BITBUCKET,
            'repository' => 'buddy-works/repman',
        ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertFalse(
            $this->container()
                ->get(DbalPackageQuery::class)
                ->getById($this->jsonResponse()['id'])
                ->isEmpty()
        );
    }

    public function testAddPackageFromBitbucketRepoNotFound(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_BITBUCKET);
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_BITBUCKET,
            'repository' => 'buddy-works/missing',
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "repository": [
                        "Repository \'buddy-works/missing\' not found."
                    ]
                }
            }
            '
        );
    }

    public function testAddPackageMissingBitbucketRepoName(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_BITBUCKET);
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_BITBUCKET,
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "repository": [
                        "This value should not be blank."
                    ]
                }
            }
            '
        );
    }

    public function testAddPackageMissingBitbucketIntegration(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => OAuthToken::TYPE_BITBUCKET,
            'repository' => 'buddy-works/repman',
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "type": [
                        "Missing bitbucket integration."
                    ]
                }
            }
            '
        );
    }

    public function testAddPackageMissingType(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'url' => 'www.url.com',
        ]));

        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "type": [
                        "This value should not be null."
                    ]
                }
            }
            '
        );

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testAddPackageInvalidType(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => 'invalid',
            'url' => 'www.url.com',
        ]));

        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "type": [
                        "This value is not valid."
                    ]
                }
            }
            '
        );

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testAddPackageMissingUrl(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_package_add', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'type' => 'git',
        ]));

        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "url": [
                        "This value should not be blank."
                    ]
                }
            }
            '
        );

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    private function jsonResponse(): array
    {
        return json_decode($this->lastResponseBody(), true);
    }
}
