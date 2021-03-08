<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Api;

use Buddy\Repman\Query\User\OrganizationQuery\DbalOrganizationQuery;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrganizationControllerTest extends FunctionalTestCase
{
    private string $apiToken;
    private static string $organization = 'Buddy works';
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $email = 'api@buddy.works';
        $this->userId = $this->fixtures->createUser($email);
        $this->fixtures->createOrganization(self::$organization, $this->userId);

        $this->apiToken = 'test-api-token';
        $this->fixtures->createApiToken($this->userId, $this->apiToken);
    }

    public function testOrganizationsList(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('GET', $this->urlTo('api_organizations'));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $json = $this->jsonResponse();
        self::assertCount(1, $json['data']);
        self::assertEquals($json['data'][0]['name'], 'Buddy works');
        self::assertEquals($json['data'][0]['alias'], 'buddy-works');
        self::assertEquals($json['data'][0]['hasAnonymousAccess'], false);
        self::assertEquals($json['total'], 1);
        self::assertNotEmpty($json['links']);
    }

    public function testOrganizationsListPagination(): void
    {
        for ($i = 1; $i <= 41; ++$i) {
            $this->fixtures->createOrganization("test-list-name#$i", $this->userId);
        }

        $this->loginApiUser($this->apiToken);
        $this->client->request('GET', $this->urlTo('api_organizations', ['page' => 2]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $json = $this->jsonResponse();

        self::assertCount(20, $json['data']);
        self::assertEquals($json['data'][0]['name'], 'test-list-name#27');
        self::assertEquals($json['data'][19]['name'], 'test-list-name#7');

        self::assertEquals($json['total'], 42);

        $baseUrl = $this->urlTo('api_organizations', [], UrlGeneratorInterface::ABSOLUTE_URL);
        self::assertEquals($baseUrl.'?page=1', $json['links']['first']);
        self::assertEquals($baseUrl.'?page=1', $json['links']['prev']);
        self::assertEquals($baseUrl.'?page=3', $json['links']['next']);
        self::assertEquals($baseUrl.'?page=3', $json['links']['last']);
    }

    public function testCreateOrganization(): void
    {
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_organization_create'), [], [], [], (string) json_encode([
            'name' => 'New organization',
        ]));

        $json = $this->jsonResponse();

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertEquals($json['name'], 'New organization');
        self::assertEquals($json['alias'], 'new-organization');
        self::assertEquals($json['hasAnonymousAccess'], false);
        self::assertFalse(
            $this->container()
                ->get(DbalOrganizationQuery::class)
                ->getByAlias($json['alias'])
                ->isEmpty()
        );
    }

    public function testCreateOrganizationBadRequest(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_organization_create'));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": [
                    {
                        "field": "name",
                        "message": "This value should not be blank."
                    }
                ]
            }
            '
        );
    }

    public function testCreateOrganizationAlreadyExists(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_organization_create'), [], [], [], (string) json_encode([
            'name' => self::$organization,
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": [
                    {
                        "field": "name",
                        "message": "Organization \"Buddy works\" already exists."
                    }
                ]
            }
            '
        );
    }

    /**
     * @return array<mixed>
     */
    private function jsonResponse(): array
    {
        return json_decode($this->lastResponseBody(), true);
    }
}
