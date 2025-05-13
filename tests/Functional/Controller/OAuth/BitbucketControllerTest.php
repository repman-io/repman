<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\OAuth;

use Buddy\Repman\Service\Integration\BitbucketApi;
use Buddy\Repman\Tests\Doubles\BitbucketOAuth;
use Buddy\Repman\Tests\Doubles\HttpClientStub;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use GuzzleHttp\Psr7\Response;
use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
use Symfony\Component\HttpFoundation\Request;

final class BitbucketControllerTest extends FunctionalTestCase
{
    public function testStartRegisterWithBitbucket(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('register_bitbucket_start'));
        $response = $this->client->getResponse();

        $this->assertStringContainsString('bitbucket.org', (string) $response->headers->get('location'));
    }

    public function testStartAuthWithBitbucket(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('auth_bitbucket_start'));
        $response = $this->client->getResponse();

        $this->assertStringContainsString('bitbucket.org', (string) $response->headers->get('location'));
    }

    public function testRedirectToIndexWhenAlreadyLogged(): void
    {
        $this->createAndLoginAdmin();
        $this->client->request(Request::METHOD_GET, $this->urlTo('register_bitbucket_check'));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
    }

    public function testLoginUserIfAlreadyExist(): void
    {
        $this->fixtures->createUser($email = 'test@buddy.works');

        $this->client->request(Request::METHOD_GET, $this->urlTo('auth_bitbucket_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create')));
        $this->client->followRedirect();

        $this->assertStringContainsString('Your account already exists', $this->lastResponseBody());
    }

    public function testCreateUserIfNotExists(): void
    {
        $email = 'test@buddy.works';
        $this->client->request(Request::METHOD_GET, $this->urlTo('auth_bitbucket_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create', ['origin' => 'bitbucket'])));
        $this->client->followRedirect();

        $this->assertStringContainsString('Your account has been created', $this->lastResponseBody());
    }

    public function testSuccessfulLoginWithBitbucket(): void
    {
        $this->fixtures->createOAuthUser($email = 'test@buddy.works');
        $this->client->request(Request::METHOD_GET, $this->urlTo('auth_bitbucket_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request(Request::METHOD_GET, $this->urlTo('login_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->followRedirect();
        $this->assertStringContainsString('test@buddy.works', $this->lastResponseBody());
    }

    public function testDisplayErrorIfSomethingGoesWrongDuringRegister(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('register_bitbucket_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        $this->container()->get(HttpClientStub::class)->setNextResponses([new Response(200, [], '{"error_description":"invalid scope provided"}')]);

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_register')));
        $this->client->followRedirect();

        $this->assertStringContainsString('invalid scope provided', $this->lastResponseBody());
    }

    public function testDisplayErrorIfMissingAuthorizationCodeExceptionIsThrow(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('register_bitbucket_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockAccessTokenResponse('whatever@repman.io', $this->container());
        $this->container()->get(BitbucketApi::class)->setExceptionOnNextCall(new MissingAuthorizationCodeException());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_register')));
        $this->client->followRedirect();

        $this->assertStringContainsString('Authentication failed! Did you authorize our app?', $this->lastResponseBody());
    }

    public function testAddOAuthTokenToUser(): void
    {
        $userId = $this->createAndLoginAdmin($email = 'test@buddy.works');
        $this->fixtures->createOrganization('buddy', $userId);
        $this->client->request(Request::METHOD_GET, $this->urlTo('fetch_bitbucket_package_token', ['organization' => 'buddy']));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request(Request::METHOD_GET, $this->urlTo('package_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'bitbucket'])));
        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testHandleOAuthErrorDuringTokenFetching(): void
    {
        $userId = $this->createAndLoginAdmin($email = 'test@buddy.works');
        $this->fixtures->createOrganization('buddy', $userId);
        $this->client->request(Request::METHOD_GET, $this->urlTo('fetch_bitbucket_package_token', ['organization' => 'buddy']));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockInvalidAccessTokenResponse($error = 'Bitbucket is down, we are sorry :(', $this->container());

        $this->client->request(Request::METHOD_GET, $this->urlTo('package_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_package_new', ['organization' => 'buddy'])));
        $this->client->followRedirect();

        $this->assertStringContainsString($error, $this->lastResponseBody());
    }

    public function testAddPackageFromBitbucketWithoutToken(): void
    {
        $userId = $this->createAndLoginAdmin();
        $this->fixtures->createOrganization('buddy', $userId);
        $this->client->request(Request::METHOD_GET, $this->urlTo('fetch_bitbucket_package_token', ['organization' => 'buddy']));

        $this->assertStringContainsString('bitbucket.org', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testAddPackageFromBitbucketWithToken(): void
    {
        $userId = $this->createAndLoginAdmin();
        $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'bitbucket');

        $this->client->request(Request::METHOD_GET, $this->urlTo('fetch_bitbucket_package_token', ['organization' => 'buddy']));

        $this->assertTrue($this->client
            ->getResponse()
            ->isRedirect($this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'bitbucket'])));
    }

    /**
     * @return mixed[]
     */
    private function getQueryParamsFromLastResponse(): array
    {
        parse_str((string) parse_url((string) $this->client->getResponse()->headers->get('location'), PHP_URL_QUERY), $params);

        return $params;
    }
}
