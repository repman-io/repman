<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Api;

use Buddy\Repman\Query\User\OrganizationQuery\DbalOrganizationQuery;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
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
        $this->client->request('GET', $this->urlTo('api_tokens', ['organization' => self::$organization]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $json = $this->jsonResponse();
        self::assertEquals(count($json['data']), 1);
        self::assertEquals($json['data'][0]['name'], 'test-list-name');
        self::assertEquals($json['data'][0]['value'], 'test-list-value');
        self::assertNotEmpty($json['data'][0]['createdAt']);
        self::assertEquals($json['data'][0]['lastUsedAt'], null);
        self::assertEquals($json['total'], 1);
        self::assertNotEmpty($json['links']);
    }

    public function testTokensListPagination(): void
    {
        for ($i = 1; $i <= 41; ++$i) {
            $this->fixtures->createToken($this->organizationId, "test-list-value#$i", "test-list-name#$i");
        }

        $this->loginApiUser($this->apiToken);
        $this->client->request('GET', $this->urlTo('api_tokens', [
            'organization' => self::$organization,
            'page' => 2,
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $json = $this->jsonResponse();

        self::assertEquals(count($json['data']), 20);
        self::assertEquals($json['data'][0]['name'], 'test-list-name#28');
        self::assertEquals($json['data'][19]['name'], 'test-list-name#8');

        self::assertEquals($json['total'], 41);

        $baseUrl = $this->urlTo('api_tokens', ['organization' => self::$organization], UrlGeneratorInterface::ABSOLUTE_URL);
        self::assertEquals($baseUrl.'?page=1', $json['links']['first']);
        self::assertEquals($baseUrl.'?page=1', $json['links']['prev']);
        self::assertEquals($baseUrl.'?page=3', $json['links']['next']);
        self::assertEquals($baseUrl.'?page=3', $json['links']['last']);
    }

    public function testEmptyTokensList(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('GET', $this->urlTo('api_tokens', ['organization' => self::$organization]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $json = $this->jsonResponse();
        self::assertEquals($json['data'], []);
        self::assertEquals($json['total'], 0);

        $baseUrl = $this->urlTo('api_tokens', ['organization' => self::$organization], UrlGeneratorInterface::ABSOLUTE_URL);
        self::assertEquals($baseUrl.'?page=1', $json['links']['first']);
        self::assertEquals(null, $json['links']['prev']);
        self::assertEquals(null, $json['links']['next']);
        self::assertEquals($baseUrl.'?page=1', $json['links']['last']);
    }

    public function testGenerateToken(): void
    {
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_token_generate', [
            'organization' => self::$organization,
        ]), [], [], [], (string) json_encode([
            'name' => 'new-token',
        ]));

        $json = $this->jsonResponse();

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertEquals($json['name'], 'new-token');
        self::assertNotEmpty($json['createdAt']);
        self::assertEquals($json['lastUsedAt'], null);
        self::assertFalse(
            $this->container()
                ->get(DbalOrganizationQuery::class)
                ->findToken($this->organizationId, $json['value'])
                ->isEmpty()
        );
    }

    public function testInvalidJson(): void
    {
        $this->loginApiUser($this->apiToken);

        $this->client->request('POST', $this->urlTo('api_token_generate', [
            'organization' => self::$organization,
        ]), [], [], [], 'invalid');

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testGenerateTokenBadRequest(): void
    {
        $this->loginApiUser($this->apiToken);
        $this->client->request('POST', $this->urlTo('api_token_generate', [
            'organization' => self::$organization,
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            $this->lastResponseBody(),
            '
            {
                "errors": {
                    "name": [
                        "This value should not be blank."
                    ]
                }
            }
            '
        );
    }

    public function testRemoveToken(): void
    {
        $this->fixtures->createToken($this->organizationId, 'test-remove-value', 'test-remove-name');
        $this->loginApiUser($this->apiToken);

        $this->client->request('DELETE', $this->urlTo('api_token_remove', [
            'organization' => self::$organization,
            'token' => 'test-remove-value',
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertTrue(
            $this->container()
                ->get(DbalOrganizationQuery::class)
                ->findToken($this->organizationId, 'test-remove-value')
                ->isEmpty()
        );
    }

    public function testRemoveTokenNonExisting(): void
    {
        $this->fixtures->createToken($this->organizationId, 'test-remove-value', 'test-remove-name');
        $this->loginApiUser($this->apiToken);

        $this->client->request('DELETE', $this->urlTo('api_token_remove', [
            'organization' => self::$organization,
            'token' => 'not exists',
        ]));

        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testRegenerateToken(): void
    {
        $this->fixtures->createToken($this->organizationId, 'test-regenerate-value', 'test-regenerate-name');
        $this->loginApiUser($this->apiToken);

        $this->client->request('PUT', $this->urlTo('api_token_regenerate', [
            'organization' => self::$organization,
            'token' => 'test-regenerate-value',
        ]));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertNotEquals(
            $this->container()
                ->get(DbalOrganizationQuery::class)
                ->findTokenByName($this->organizationId, 'test-regenerate-name')
                ->get()
                ->value(),
            'test-regenerate-value'
        );
    }

    public function testRegenerateTokenNonExisting(): void
    {
        $this->fixtures->createToken($this->organizationId, 'test-regenerate-value', 'test-regenerate-name');
        $this->loginApiUser($this->apiToken);

        $this->client->request('PUT', $this->urlTo('api_token_regenerate', [
            'organization' => self::$organization,
            'token' => 'not exists',
        ]));

        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    private function jsonResponse(): array
    {
        return json_decode($this->lastResponseBody(), true);
    }
}
