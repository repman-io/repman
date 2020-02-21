<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\OAuth;

use Buddy\Repman\Service\GitHubApi;
use Buddy\Repman\Tests\Doubles\HttpClientStub;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use GuzzleHttp\Psr7\Response;

final class GitHubControllerTest extends FunctionalTestCase
{
    public function testStartRegisterWithGitHub(): void
    {
        $this->client->request('GET', $this->urlTo('register_github_start'));
        $response = $this->client->getResponse();

        self::assertStringContainsString('github.com', (string) $response->headers->get('location'));
    }

    public function testStartAuthWithGitHub(): void
    {
        $this->client->request('GET', $this->urlTo('auth_github_start'));
        $response = $this->client->getResponse();

        self::assertStringContainsString('github.com', (string) $response->headers->get('location'));
    }

    public function testRedirectToIndexWhenAlreadyLogged(): void
    {
        $this->createAndLoginAdmin();
        $this->client->request('GET', $this->urlTo('register_github_check'));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
    }

    public function testLoginUserIfAlreadyExist(): void
    {
        $this->fixtures->createUser($email = 'test@buddy.works');

        $this->client->request('GET', $this->urlTo('auth_github_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        $this->container()->get(GitHubApi::class)->setPrimaryEmail($email);
        $this->container()->get(HttpClientStub::class)->setNextResponse(new Response(200, [], '{"access_token":"e72e16c7e42f292c6912e7710c838347ae178b4a", "scope":"user:email", "token_type":"bearer"}'));
        $this->client->request('GET', $this->urlTo('register_github_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create')));
        $this->client->followRedirect();

        self::assertStringContainsString('Your account already exists', $this->lastResponseBody());
    }

    public function testCreateUserIfNotExistsExist(): void
    {
        $email = 'test@buddy.works';
        $this->client->request('GET', $this->urlTo('auth_github_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        $this->container()->get(GitHubApi::class)->setPrimaryEmail($email);
        $this->container()->get(HttpClientStub::class)->setNextResponse(new Response(200, [], '{"access_token":"e72e16c7e42f292c6912e7710c838347ae178b4a", "scope":"user:email", "token_type":"bearer"}'));

        $this->client->request('GET', $this->urlTo('register_github_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create')));
        $this->client->followRedirect();

        self::assertStringContainsString('Your account has been created', $this->lastResponseBody());
    }

    public function testSuccessfulLoginWithGithub(): void
    {
        $this->fixtures->createGithubUser($email = 'test@buddy.works');
        $this->client->request('GET', $this->urlTo('auth_github_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        $this->container()->get(GitHubApi::class)->setPrimaryEmail($email);
        $this->container()->get(HttpClientStub::class)->setNextResponse(new Response(200, [], '{"access_token":"e72e16c7e42f292c6912e7710c838347ae178b4a", "scope":"user:email", "token_type":"bearer"}'));

        $this->client->request('GET', $this->urlTo('login_github_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->followRedirect();
        self::assertStringContainsString('test@buddy.works', $this->lastResponseBody());
    }

    public function testDisplayErrorIfSomethingGoesWrongDuringRegister(): void
    {
        $this->client->request('GET', $this->urlTo('register_github_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        $this->container()->get(HttpClientStub::class)->setNextResponse(new Response(200, [], '{"error":"invalid scope provided"}'));

        $this->client->request('GET', $this->urlTo('register_github_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_register')));
        $this->client->followRedirect();

        self::assertStringContainsString('invalid scope provided', $this->lastResponseBody());
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
