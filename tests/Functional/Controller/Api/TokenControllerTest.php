<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Api;

use Buddy\Repman\Query\Api\OrganizationQuery\DbalOrganizationQuery;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TokenControllerTest extends FunctionalTestCase
{
    private string $apiToken;

    private string $organizationId;

    private static string $organization = 'buddy';

    protected function setUp(): void
    {
        parent::setUp();

        $email = 'api@buddy.works';
        $userId = $this->fixtures->createUser($email);
        $this->organizationId = $this->fixtures->createOrganization(self::$organization, $userId);

        $this->apiToken = 'test-api-token';
        $this->fixtures->createApiToken($userId, $this->apiToken);
    }

    public function testTokensList(): void
    {
        $this->fixtures->createToken($this->organizationId, 'test-list-value', 'test-list-name');

        $this->loginApiUser($this->apiToken);
        $this->client->request(Request::METHOD_GET, $this->urlTo('api_tokens', ['organization' => self::$organization]));

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $json = $this->jsonResponse();
        $this->assertCount(1, $json['data']);
        $this->assertSame('test-list-name', $json['data'][0]['name']);
        $this->assertSame('test-list-value', $json['data'][0]['value']);
        $this->assertNotEmpty($json['data'][0]['createdAt']);
        $this->assertEquals($json['data'][0]['lastUsedAt'], null);
        $this->assertSame(1, $json['total']);
        $this->assertNotEmpty($json['links']);
    }

    public function testTokensListPagination(): void
    {
        for ($i = 1; $i <= 41; ++$i) {
            $this->fixtures->createToken($this->organizationId, 'test-list-value#'.$i, 'test-list-name#'.$i);
        }

        $this->loginApiUser($this->apiToken);
        $this->client->request(Request::METHOD_GET, $this->urlTo('api_tokens', [
            'organization' => self::$organization,
            'page' => 2,
        ]));

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $json = $this->jsonResponse();

        $this->assertCount(20, $json['data']);
        $this->assertSame('test-list-name#28', $json['data'][0]['name']);
        $this->assertSame('test-list-name#8', $json['data'][19]['name']);

        $this->assertSame(41, $json['total']);

        $baseUrl = $this->urlTo('api_tokens', ['organization' => self::$organization], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame($baseUrl.'?page=1', $json['links']['first']);
        $this->assertSame($baseUrl.'?page=1', $json['links']['prev']);
        $this->assertSame($baseUrl.'?page=3', $json['links']['next']);
        $this->assertSame($baseUrl.'?page=3', $json['links']['last']);
    }

    public function testEmptyTokensList(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request(Request::METHOD_GET, $this->urlTo('api_tokens', ['organization' => self::$organization]));

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $json = $this->jsonResponse();
        $this->assertEquals($json['data'], []);
        $this->assertSame(0, $json['total']);

        $baseUrl = $this->urlTo('api_tokens', ['organization' => self::$organization], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame($baseUrl.'?page=1', $json['links']['first']);
        $this->assertEquals(null, $json['links']['prev']);
        $this->assertEquals(null, $json['links']['next']);
        $this->assertSame($baseUrl.'?page=1', $json['links']['last']);
    }

    public function testGenerateToken(): void
    {
        $this->loginApiUser($this->apiToken);

        $this->client->request(Request::METHOD_POST, $this->urlTo('api_token_generate', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'name' => 'new-token',
        ]));

        $json = $this->jsonResponse();

        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertSame('new-token', $json['name']);
        $this->assertNotEmpty($json['createdAt']);
        $this->assertNotEmpty($json['value']);
        $this->assertEquals($json['lastUsedAt'], null);
        $this->assertFalse($this->container()
            ->get(DbalOrganizationQuery::class)
            ->findToken($this->organizationId, $json['value'])
            ->isEmpty());
    }

    public function testInvalidJson(): void
    {
        $this->loginApiUser($this->apiToken);

        $this->client->request(Request::METHOD_POST, $this->urlTo('api_token_generate', [
            'organization' => self::$organization,
        ]), [], [], [], 'invalid');

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testGenerateTokenBadRequest(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request(Request::METHOD_POST, $this->urlTo('api_token_generate', [
            'organization' => self::$organization,
        ]));

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

    public function testRemoveToken(): void
    {
        $this->fixtures->createToken($this->organizationId, 'test-remove-value', 'test-remove-name');
        $this->loginApiUser($this->apiToken);

        $this->client->request(Request::METHOD_DELETE, $this->urlTo('api_token_remove', [
            'organization' => self::$organization,
            'token' => 'test-remove-value',
        ]));

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertTrue($this->container()
            ->get(DbalOrganizationQuery::class)
            ->findToken($this->organizationId, 'test-remove-value')
            ->isEmpty());
    }

    public function testRemoveTokenNonExisting(): void
    {
        $this->fixtures->createToken($this->organizationId, 'test-remove-value', 'test-remove-name');
        $this->loginApiUser($this->apiToken);

        $this->client->request(Request::METHOD_DELETE, $this->urlTo('api_token_remove', [
            'organization' => self::$organization,
            'token' => 'not exists',
        ]));

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testRegenerateToken(): void
    {
        $this->fixtures->createToken($this->organizationId, 'test-regenerate-value', 'test-regenerate-name');
        $this->loginApiUser($this->apiToken);

        $this->client->request(Request::METHOD_PUT, $this->urlTo('api_token_regenerate', [
            'organization' => self::$organization,
            'token' => 'test-regenerate-value',
        ]));

        $json = $this->jsonResponse();
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertNotEquals($this->container()
            ->get(DbalOrganizationQuery::class)
            ->findTokenByName($this->organizationId, 'test-regenerate-name')
            ->get()
            ->getValue(), 'test-regenerate-value');
        $this->assertSame('test-regenerate-name', $json['name']);
        $this->assertNotEmpty($json['createdAt']);
        $this->assertNotEmpty($json['value']);
        $this->assertEquals($json['lastUsedAt'], null);
    }

    public function testRegenerateTokenNonExisting(): void
    {
        $this->fixtures->createToken($this->organizationId, 'test-regenerate-value', 'test-regenerate-name');
        $this->loginApiUser($this->apiToken);

        $this->client->request(Request::METHOD_PUT, $this->urlTo('api_token_regenerate', [
            'organization' => self::$organization,
            'token' => 'not exists',
        ]));

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    /**
     * @return array<mixed>
     */
    private function jsonResponse(): array
    {
        return json_decode($this->lastResponseBody(), true);
    }
}
