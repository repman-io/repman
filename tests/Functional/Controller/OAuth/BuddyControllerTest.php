<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\OAuth;

use Buddy\Repman\Tests\Doubles\BuddyOAuth;
use Buddy\Repman\Tests\Doubles\HttpClientStub;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;

final class BuddyControllerTest extends FunctionalTestCase
{
    public function testStartRegisterWithBuddy(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('register_buddy_start'));
        $response = $this->client->getResponse();

        $this->assertStringContainsString('buddy.works', (string) $response->headers->get('location'));
    }

    public function testStartAuthWithBuddy(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('auth_buddy_start'));
        $response = $this->client->getResponse();

        $this->assertStringContainsString('buddy.works', (string) $response->headers->get('location'));
    }

    public function testRedirectToIndexWhenAlreadyLogged(): void
    {
        $this->createAndLoginAdmin();
        $this->client->request(Request::METHOD_GET, $this->urlTo('register_buddy_check'));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
    }

    public function testLoginUserIfAlreadyExist(): void
    {
        $this->fixtures->createUser($email = 'test@buddy.works');

        $this->client->request(Request::METHOD_GET, $this->urlTo('auth_buddy_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BuddyOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_buddy_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create')));
        $this->client->followRedirect();

        $this->assertStringContainsString('Your account already exists', $this->lastResponseBody());
    }

    public function testCreateUserIfNotExists(): void
    {
        $email = 'test@buddy.works';
        $this->client->request(Request::METHOD_GET, $this->urlTo('auth_buddy_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BuddyOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_buddy_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create', ['origin' => 'buddy'])));
        $this->client->followRedirect();

        $this->assertStringContainsString('Your account has been created', $this->lastResponseBody());
    }

    public function testSuccessfulLoginWithBuddy(): void
    {
        $this->fixtures->createOAuthUser($email = 'test@buddy.works');
        $this->client->request(Request::METHOD_GET, $this->urlTo('auth_buddy_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BuddyOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request(Request::METHOD_GET, $this->urlTo('login_buddy_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->followRedirect();
        $this->assertStringContainsString('test@buddy.works', $this->lastResponseBody());
    }

    public function testDisplayErrorIfSomethingGoesWrongDuringRegister(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('register_buddy_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        $this->container()->get(HttpClientStub::class)->setNextResponses([new Response(200, [], '{"errors":[{"message":"invalid scope provided"}]}')]);

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_buddy_check', ['state' => $params['state'], 'code' => 'secret-token']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_register')));
        $this->client->followRedirect();

        $this->assertStringContainsString('invalid scope provided', $this->lastResponseBody());
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
