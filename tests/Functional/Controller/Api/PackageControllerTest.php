<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Api;

use Buddy\Repman\Entity\Organization\Package\Metadata;
use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Query\User\PackageQuery\DbalPackageQuery;
use Buddy\Repman\Service\Integration\BitbucketApi;
use Buddy\Repman\Service\Integration\GitHubApi;
use Buddy\Repman\Service\Integration\GitLabApi;
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
                "errors": [
                    {
                        "field": "credentials",
                        "message": "Authentication required."
                    }
                ]
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
                "errors": [
                    {
                        "field": "credentials",
                        "message": "Invalid credentials."
                    }
                ]
            }
            '
        );
    }

    public function testOrganizationAccessDenied(): void
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
                        "type": "vcs",
                        "url": "https://github.com/buddy-works/repman",
                        "name": null,
                        "latestReleasedVersion": null,
                        "latestReleaseDate": null,
                        "description": null,
                        "enableSecurityScan":true,
                        "lastSyncAt": null,
                        "lastSyncError": null,
                        "webhookCreatedAt": null,
                        "isSynchronizedSuccessfully": false,
                        "keepLastReleases": 0,
                        "scanResultStatus": "pending",
                        "scanResultDate": null,
                        "lastScanResultContent": []
                    }
                ],
                "total": 1,
                "links": {
                    "first": "http://localhost/api/organization/buddy/package?page=1",
                    "last": "http://localhost/api/organization/buddy/package?page=1",
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

        self::assertCount(20, $json['data']);
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
        $release = new \DateTimeImmutable('2020-01-01 12:12:12');
        $this->fixtures->createPackage($packageId, '', $this->organizationId);
        $this->fixtures
            ->syncPackageWithData(
                $packageId,
                'buddy-works/repman',
                'Repository manager',
                '2.1.1',
                $release
            );
        $this->fixtures->addScanResult($packageId, 'ok');

        $this->loginApiUser($this->apiToken);
        $now = (new \DateTimeImmutable())->format(\DateTime::ATOM);
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
                "type": "vcs",
                "url": "https://github.com/buddy-works/repman",
                "name": "buddy-works/repman",
                "latestReleasedVersion": "2.1.1",
                "latestReleaseDate": "'.$release->format(\DateTime::ATOM).'",
                "description": "Repository manager",
                "enableSecurityScan": true,
                "lastSyncAt": "'.$now.'",
                "lastSyncError": null,
                "webhookCreatedAt": null,
                "isSynchronizedSuccessfully": true,
                "keepLastReleases": 0,
                "scanResultStatus": "ok",
                "scanResultDate": "'.$now.'",
                "lastScanResultContent": {
                    "composer.lock": []
                }
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

    public function testRemovePackageWhenBitbucketWebhookRemovalFailed(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_BITBUCKET);
        $packageId = $this->fixtures->addPackage($this->organizationId, 'https://buddy.com', 'bitbucket-oauth', [Metadata::BITBUCKET_REPO_NAME => 'some/repo']);
        $this->fixtures->setWebhookCreated($packageId);

        $this->loginApiUser($this->apiToken);

        $this->container()->get(BitbucketApi::class)->setExceptionOnNextCall(new \RuntimeException('Webhook already removed'));
        $this->client->request('DELETE', $this->urlTo('api_package_remove', [
            'organization' => self::$organization,
            'package' => $packageId,
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertMatchesPattern('
            {
                "warning": "@string@.contains(\'Webhook already removed\')"
            }
        ', (string) $this->client->getResponse()->getContent());

        self::assertTrue(
            $this->container()
                ->get(DbalPackageQuery::class)
                ->getById($packageId)
                ->isEmpty()
        );
    }

    public function testRemovePackageWhenGithubWebhookRemovalFailed(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);
        $packageId = $this->fixtures->addPackage($this->organizationId, 'https://buddy.com', 'github-oauth', [Metadata::GITHUB_REPO_NAME => 'org/repo']);
        $this->fixtures->setWebhookCreated($packageId);

        $this->loginApiUser($this->apiToken);

        $this->container()->get(GitHubApi::class)->setExceptionOnNextCall(new \RuntimeException('Webhook already removed'));
        $this->client->request('DELETE', $this->urlTo('api_package_remove', [
            'organization' => self::$organization,
            'package' => $packageId,
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertMatchesPattern('
            {
                "warning": "@string@.contains(\'Webhook already removed\')"
            }
        ', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->container()->get(DbalPackageQuery::class)->getById($packageId)->isEmpty());
    }

    public function testRemovePackageWhenGitlabWebhookRemovalFailed(): void
    {
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITLAB);
        $packageId = $this->fixtures->addPackage($this->organizationId, 'https://buddy.com', 'gitlab-oauth', [Metadata::GITLAB_PROJECT_ID => 1234]);
        $this->fixtures->setWebhookCreated($packageId);

        $this->loginApiUser($this->apiToken);

        $this->container()->get(GitLabApi::class)->setExceptionOnNextCall(new \RuntimeException('Webhook already removed'));
        $this->client->request('DELETE', $this->urlTo('api_package_remove', [
            'organization' => self::$organization,
            'package' => $packageId,
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertMatchesPattern('
            {
                "warning": "@string@.contains(\'Webhook already removed\')"
            }
        ', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->container()->get(DbalPackageQuery::class)->getById($packageId)->isEmpty());
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

    public function testSynchronizePackage(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId, '', $this->organizationId);

        $this->loginApiUser($this->apiToken);
        $this->client->request('PUT', $this->urlTo('api_package_update', [
            'organization' => self::$organization,
            'package' => $packageId,
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSynchronizePackageNonExisting(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('PUT', $this->urlTo('api_package_update', [
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
        $this->client->request('PATCH', $this->urlTo('api_package_update', [
            'organization' => self::$organization,
            'package' => $packageId,
        ]), [], [], [], (string) json_encode([
            'url' => 'new-url',
            'keepLastReleases' => 6,
            'enableSecurityScan' => true,
        ]));

        $package = $this->container()->get(DbalPackageQuery::class)->getById($packageId)->get();

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertEquals($package->url(), 'new-url');
        self::assertEquals($package->keepLastReleases(), 6);
    }

    public function testUpdatePackageBadRequest(): void
    {
        $packageId = Uuid::uuid4()->toString();
        $this->fixtures->createPackage($packageId, '', $this->organizationId);

        $this->loginApiUser($this->apiToken);
        $this->client->request('PATCH', $this->urlTo('api_package_update', [
            'organization' => self::$organization,
            'package' => $packageId,
        ]), [], [], [], (string) json_encode([
            'keepLastReleases' => 10.5,
        ]));

        $package = $this->container()->get(DbalPackageQuery::class)->getById($packageId)->get();

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": [
                    {
                        "field": "keepLastReleases",
                        "message": "This value is not valid."
                    }
                ]
            }
            '
        );
        self::assertEquals($package->keepLastReleases(), 0);
    }

    public function testUpdatePackageNonExisting(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('PATCH', $this->urlTo('api_package_update', [
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
            'repository' => 'https://github.com/buddy/test-composer-package',
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
            'repository' => '/path/to/package',
        ]));
        $now = (new \DateTimeImmutable())->format(\DateTime::ATOM);

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString($this->lastResponseBody(),
            '
            {
                "id": "'.$this->jsonResponse()['id'].'",
                "type": "path",
                "url": "/path/to/package",
                "name": "default/default",
                "latestReleasedVersion": "1.0.0",
                "latestReleaseDate": "'.$now.'",
                "description": "n/a",
                "enableSecurityScan": true,
                "lastSyncAt": "'.$now.'",
                "lastSyncError": null,
                "webhookCreatedAt": null,
                "isSynchronizedSuccessfully": true,
                "keepLastReleases": 0,
                "scanResultStatus": "pending",
                "scanResultDate": null,
                "lastScanResultContent": []
            }
            '
        );
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
                "errors": [
                    {
                        "field": "repository",
                        "message": "This value should not be blank."
                    }
                ]
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
                "errors": [
                    {
                        "field": "type",
                        "message": "Missing github integration."
                    }
                ]
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
                "errors": [
                    {
                        "field": "repository",
                        "message": "Repository \'buddy-works/missing\' not found."
                    }
                ]
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
                "errors": [
                    {
                        "field": "repository",
                        "message": "This value should not be blank."
                    }
                ]
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
                "errors": [
                    {
                        "field": "type",
                        "message": "Missing gitlab integration."
                    }
                ]
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
                "errors": [
                    {
                        "field": "repository",
                        "message": "Repository \'buddy-works/missing\' not found."
                    }
                ]
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
                "errors": [
                    {
                        "field": "repository",
                        "message": "This value should not be blank."
                    }
                ]
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
                "errors": [
                    {
                        "field": "type",
                        "message": "Missing bitbucket integration."
                    }
                ]
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
            'repository' => 'www.url.com',
        ]));

        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": [
                    {
                        "field": "type",
                        "message": "This value should not be blank."
                    }
                ]
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
            'repository' => 'www.url.com',
        ]));

        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": [
                    {
                        "field": "type",
                        "message": "This value is not valid."
                    }
                ]
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
                "errors": [
                    {
                        "field": "repository",
                        "message": "This value should not be blank."
                    }
                ]
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
