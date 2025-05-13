<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Api;

use Buddy\Repman\Query\User\OrganizationQuery\DbalOrganizationQuery;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;
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
        $this->client->request(Request::METHOD_GET, $this->urlTo('api_organizations'));

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $json = $this->jsonResponse();
        $this->assertCount(1, $json['data']);
        $this->assertSame('Buddy works', $json['data'][0]['name']);
        $this->assertSame('buddy-works', $json['data'][0]['alias']);
        $this->assertEquals($json['data'][0]['hasAnonymousAccess'], false);
        $this->assertSame(1, $json['total']);
        $this->assertNotEmpty($json['links']);
    }

    public function testOrganizationsListPagination(): void
    {
        for ($i = 1; $i <= 41; ++$i) {
            $this->fixtures->createOrganization('test-list-name#'.$i, $this->userId);
        }

        $this->loginApiUser($this->apiToken);
        $this->client->request(Request::METHOD_GET, $this->urlTo('api_organizations', ['page' => 2]));

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $json = $this->jsonResponse();

        $this->assertCount(20, $json['data']);
        $this->assertSame('test-list-name#27', $json['data'][0]['name']);
        $this->assertSame('test-list-name#7', $json['data'][19]['name']);

        $this->assertSame(42, $json['total']);

        $baseUrl = $this->urlTo('api_organizations', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame($baseUrl.'?page=1', $json['links']['first']);
        $this->assertSame($baseUrl.'?page=1', $json['links']['prev']);
        $this->assertSame($baseUrl.'?page=3', $json['links']['next']);
        $this->assertSame($baseUrl.'?page=3', $json['links']['last']);
    }

    public function testCreateOrganization(): void
    {
        $this->loginApiUser($this->apiToken);

        $this->client->request(Request::METHOD_POST, $this->urlTo('api_organization_create'), [], [], [], (string) json_encode([
            'name' => 'New organization',
        ]));

        $json = $this->jsonResponse();

        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertSame('New organization', $json['name']);
        $this->assertSame('new-organization', $json['alias']);
        $this->assertEquals($json['hasAnonymousAccess'], false);
        $this->assertFalse($this->container()
            ->get(DbalOrganizationQuery::class)
            ->getByAlias($json['alias'])
            ->isEmpty());
    }

    public function testCreateOrganizationBadRequest(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request(Request::METHOD_POST, $this->urlTo('api_organization_create'));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString($this->lastResponseBody(), '
            {
                "errors": [
                    {
                        "field": "name",
                        "message": "This value should not be blank."
                    }
                ]
            }
            ');
    }

    public function testCreateOrganizationAlreadyExists(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request(Request::METHOD_POST, $this->urlTo('api_organization_create'), [], [], [], (string) json_encode([
            'name' => self::$organization,
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString($this->lastResponseBody(), '
            {
                "errors": [
                    {
                        "field": "name",
                        "message": "Organization \"Buddy works\" already exists."
                    }
                ]
            }
            ');
    }

    /**
     * @return array<mixed>
     */
    private function jsonResponse(): array
    {
        return json_decode($this->lastResponseBody(), true);
    }
}
