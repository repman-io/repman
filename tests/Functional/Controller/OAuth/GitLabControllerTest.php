<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\OAuth;

use Buddy\Repman\Tests\Doubles\GitLabOAuth;
use Buddy\Repman\Tests\Doubles\HttpClientStub;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use GuzzleHttp\Psr7\Response;

final class GitLabControllerTest extends FunctionalTestCase
{
    public function testStartRegisterWithGitLab(): void
    {
        $this->client->request('GET', $this->urlTo('register_gitlab_start'));
        $response = $this->client->getResponse();

        self::assertStringContainsString('gitlab.com', (string) $response->headers->get('location'));
    }

    public function testStartAuthWithGitLab(): void
    {
        $this->client->request('GET', $this->urlTo('auth_gitlab_start'));
        $response = $this->client->getResponse();

        self::assertStringContainsString('gitlab.com', (string) $response->headers->get('location'));
    }

    public function testRedirectToIndexWhenAlreadyLogged(): void
    {
        $this->createAndLoginAdmin();
        $this->client->request('GET', $this->urlTo('register_gitlab_check'));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
    }

    public function testLoginUserIfAlreadyExist(): void
    {
        $this->fixtures->createUser($email = 'test@buddy.works');

        $this->client->request('GET', $this->urlTo('auth_gitlab_start'));
        $params = $this->getQueryParamsFromLastResponse();
        $this->mockTokenAndUserResponse($email);

        $this->client->request('GET', $this->urlTo('register_gitlab_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create')));
        $this->client->followRedirect();

        self::assertStringContainsString('Your account already exists', $this->lastResponseBody());
    }

    public function testCreateUserIfNotExistsExist(): void
    {
        $email = 'test@buddy.works';
        $this->client->request('GET', $this->urlTo('auth_gitlab_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->mockTokenAndUserResponse($email);

        $this->client->request('GET', $this->urlTo('register_gitlab_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create', ['origin' => 'gitlab'])));
        $this->client->followRedirect();

        self::assertStringContainsString('Your account has been created', $this->lastResponseBody());
    }

    public function testSuccessfulLoginWithGitLab(): void
    {
        $this->fixtures->createOAuthUser($email = 'test@buddy.works');
        $this->client->request('GET', $this->urlTo('auth_gitlab_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->mockTokenAndUserResponse($email);

        $this->client->request('GET', $this->urlTo('login_gitlab_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->followRedirect();
        self::assertStringContainsString('test@buddy.works', $this->lastResponseBody());
    }

    public function testDisplayErrorIfSomethingGoesWrongDuringRegister(): void
    {
        $this->client->request('GET', $this->urlTo('register_gitlab_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        $this->container()->get(HttpClientStub::class)->setNextResponses([new Response(200, [], '{"error":"invalid scope provided"}')]);

        $this->client->request('GET', $this->urlTo('register_gitlab_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_register')));
        $this->client->followRedirect();

        self::assertStringContainsString('invalid scope provided', $this->lastResponseBody());
    }

    public function testAddOAuthTokenToUser(): void
    {
        $userId = $this->createAndLoginAdmin($email = 'test@buddy.works');
        $this->fixtures->createOrganization('buddy', $userId);
        $this->client->request('GET', $this->urlTo('fetch_gitlab_package_token', ['organization' => 'buddy']));
        $params = $this->getQueryParamsFromLastResponse();

        $this->mockTokenAndUserResponse($email);

        $this->client->request('GET', $this->urlTo('package_gitlab_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'gitlab'])));
        $this->client->followRedirect();

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testAddPackageFromGitLabWithoutToken(): void
    {
        $userId = $this->createAndLoginAdmin();
        $this->fixtures->createOrganization('buddy', $userId);
        $this->client->request('GET', $this->urlTo('fetch_gitlab_package_token', ['organization' => 'buddy']));

        self::assertStringContainsString('gitlab.com', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testAddPackageFromGitLabWithToken(): void
    {
        $userId = $this->createAndLoginAdmin();
        $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'gitlab');

        $this->client->request('GET', $this->urlTo('fetch_gitlab_package_token', ['organization' => 'buddy']));

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'gitlab']))
        );
    }

    /**
     * @return mixed[]
     */
    private function getQueryParamsFromLastResponse(): array
    {
        parse_str((string) parse_url((string) $this->client->getResponse()->headers->get('location'), PHP_URL_QUERY), $params);

        return $params;
    }

    private function mockTokenAndUserResponse(string $email): void
    {
        $this->client->disableReboot();
        GitLabOAuth::mockTokenAndUserResponse($email, $this->container());
    }
}
