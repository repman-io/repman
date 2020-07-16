<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ConfigControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAndLoginAdmin();
    }

    public function testConfigForm(): void
    {
        $this->client->request('GET', $this->urlTo('admin_config'));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Repman configuration', $this->lastResponseBody());
        self::assertStringContainsString('OAuth registration', $this->lastResponseBody());
    }

    public function testToggleAuthenticationOptions(): void
    {
        $this->client->request('GET', $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'local_authentication' => 'login_and_registration',
            'oauth_registration' => 'disabled',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_config')));
        $this->client->followRedirect();
        self::assertStringContainsString(
            'Configuration has been successfully changed',
            $this->lastResponseBody()
        );

        $this->client->request('GET', $this->urlTo('app_register'));
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('register_github_start'));
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('register_gitlab_start'));
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('register_bitbucket_start'));
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('register_buddy_start'));
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'local_authentication' => 'login_only',
            'oauth_registration' => 'disabled',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_config')));
        $this->client->followRedirect();
        self::assertStringContainsString(
            'Configuration has been successfully changed',
            $this->lastResponseBody()
        );

        $this->client->request('GET', $this->urlTo('app_register'));
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('register_github_start'));
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('register_gitlab_start'));
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('register_bitbucket_start'));
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('register_buddy_start'));
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('app_register_confirm', ['token' => '825f33c5-2311-41ec-ba18-e967027b3f6f']));
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'local_authentication' => 'login_and_registration',
            'oauth_registration' => 'enabled',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_config')));
        $this->client->followRedirect();
        self::assertStringContainsString(
            'Configuration has been successfully changed',
            $this->lastResponseBody()
        );
    }
}
