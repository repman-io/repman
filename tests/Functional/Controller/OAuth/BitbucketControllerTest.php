<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\OAuth;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Service\BitbucketApi;
use Buddy\Repman\Tests\Doubles\BitbucketOAuth;
use Buddy\Repman\Tests\Doubles\HttpClientStub;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use GuzzleHttp\Psr7\Response;
use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
use Ramsey\Uuid\Uuid;

final class BitbucketControllerTest extends FunctionalTestCase
{
    public function testStartRegisterWithBitbucket(): void
    {
        $this->client->request('GET', $this->urlTo('register_bitbucket_start'));
        $response = $this->client->getResponse();

        self::assertStringContainsString('bitbucket.org', (string) $response->headers->get('location'));
    }

    public function testStartAuthWithBitbucket(): void
    {
        $this->client->request('GET', $this->urlTo('auth_bitbucket_start'));
        $response = $this->client->getResponse();

        self::assertStringContainsString('bitbucket.org', (string) $response->headers->get('location'));
    }

    public function testRedirectToIndexWhenAlreadyLogged(): void
    {
        $this->createAndLoginAdmin();
        $this->client->request('GET', $this->urlTo('register_bitbucket_check'));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
    }

    public function testLoginUserIfAlreadyExist(): void
    {
        $this->fixtures->createUser($email = 'test@buddy.works');

        $this->client->request('GET', $this->urlTo('auth_bitbucket_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request('GET', $this->urlTo('register_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create')));
        $this->client->followRedirect();

        self::assertStringContainsString('Your account already exists', $this->lastResponseBody());
    }

    public function testCreateUserIfNotExists(): void
    {
        $email = 'test@buddy.works';
        $this->client->request('GET', $this->urlTo('auth_bitbucket_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request('GET', $this->urlTo('register_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create', ['origin' => 'bitbucket'])));
        $this->client->followRedirect();

        self::assertStringContainsString('Your account has been created', $this->lastResponseBody());
    }

    public function testSuccessfulLoginWithBitbucket(): void
    {
        $this->fixtures->createOAuthUser($email = 'test@buddy.works');
        $this->client->request('GET', $this->urlTo('auth_bitbucket_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request('GET', $this->urlTo('login_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->followRedirect();
        self::assertStringContainsString('test@buddy.works', $this->lastResponseBody());
    }

    public function testDisplayErrorIfSomethingGoesWrongDuringRegister(): void
    {
        $this->client->request('GET', $this->urlTo('register_bitbucket_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        $this->container()->get(HttpClientStub::class)->setNextResponses([new Response(200, [], '{"error_description":"invalid scope provided"}')]);

        $this->client->request('GET', $this->urlTo('register_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_register')));
        $this->client->followRedirect();

        self::assertStringContainsString('invalid scope provided', $this->lastResponseBody());
    }

    public function testDisplayErrorIfMissingAuthorizationCodeExceptionIsThrow(): void
    {
        $this->client->request('GET', $this->urlTo('register_bitbucket_start'));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockAccessTokenResponse('whatever@repman.io', $this->container());
        $this->container()->get(BitbucketApi::class)->setExceptionOnNextCall(new MissingAuthorizationCodeException());

        $this->client->request('GET', $this->urlTo('register_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_register')));
        $this->client->followRedirect();

        self::assertStringContainsString('Authentication failed! Did you authorize our app?', $this->lastResponseBody());
    }

    public function testAddOAuthTokenToUser(): void
    {
        $userId = $this->createAndLoginAdmin($email = 'test@buddy.works');
        $this->fixtures->createOrganization('buddy', $userId);
        $this->client->request('GET', $this->urlTo('fetch_bitbucket_package_token', ['organization' => 'buddy']));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockAccessTokenResponse($email, $this->container());

        $this->client->request('GET', $this->urlTo('package_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'bitbucket'])));
        $this->client->followRedirect();

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testHandleOAuthErrorDuringTokenFetching(): void
    {
        $userId = $this->createAndLoginAdmin($email = 'test@buddy.works');
        $this->fixtures->createOrganization('buddy', $userId);
        $this->client->request('GET', $this->urlTo('fetch_bitbucket_package_token', ['organization' => 'buddy']));
        $params = $this->getQueryParamsFromLastResponse();

        $this->client->disableReboot();
        BitbucketOAuth::mockInvalidAccessTokenResponse($error = 'Bitbucket is down, we are sorry :(', $this->container());

        $this->client->request('GET', $this->urlTo('package_bitbucket_check', ['state' => $params['state'], 'code' => 'secret-token']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_package_new', ['organization' => 'buddy'])));
        $this->client->followRedirect();

        self::assertStringContainsString($error, $this->lastResponseBody());
    }

    public function testRedirectToRefreshOAuthTokenWhenTokenExpired(): void
    {
        $userId = $this->createAndLoginAdmin($email = 'test@buddy.works');
        $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'bitbucket', 'old', 'refresh', (new \DateTimeImmutable())->modify('-1 hour'));

        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'bitbucket']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('refresh_oauth_token', ['type' => 'bitbucket'])));
    }

    public function testRefreshOauthToken(): void
    {
        $userId = $this->createAndLoginAdmin($email = 'test@buddy.works');
        $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'bitbucket', 'old-token', 'refresh-token');

        $this->client->disableReboot();
        BitbucketOAuth::mockRefreshTokenResponse('new-token', $this->container());

        $this->client->request('GET', $this->urlTo('refresh_oauth_token', ['type' => 'bitbucket']));
        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'bitbucket'])));

        /** @var OAuthToken $token */
        $token = $this->container()
            ->get(UserRepository::class)
            ->getById(Uuid::fromString($userId))
            ->oauthToken('bitbucket');

        self::assertEquals('new-token', $token->accessToken());
    }

    public function testAddPackageFromBitbucketWithoutToken(): void
    {
        $userId = $this->createAndLoginAdmin();
        $this->fixtures->createOrganization('buddy', $userId);
        $this->client->request('GET', $this->urlTo('fetch_bitbucket_package_token', ['organization' => 'buddy']));

        self::assertStringContainsString('bitbucket.org', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testAddPackageFromBitbucketWithToken(): void
    {
        $userId = $this->createAndLoginAdmin();
        $this->fixtures->createOrganization('buddy', $userId);
        $this->fixtures->createOauthToken($userId, 'bitbucket');

        $this->client->request('GET', $this->urlTo('fetch_bitbucket_package_token', ['organization' => 'buddy']));

        self::assertTrue(
            $this->client
                ->getResponse()
                ->isRedirect($this->urlTo('organization_package_new', ['organization' => 'buddy', 'type' => 'bitbucket']))
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
}
