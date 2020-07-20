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

    public function testEnableTelemetry(): void
    {
        $prompt = 'Help us improve <strong>Repman</strong> by enabling sending anonymous usage statistic';
        $instanceIdFile = $this->container()->getParameter('instance_id_file');
        @unlink($instanceIdFile);
        $this->client->request('GET', $this->urlTo('index'));
        self::assertStringContainsString($prompt, $this->lastResponseBody());

        $this->client->request('POST', $this->urlTo('admin_config_telemetry_enable'));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->followRedirect();

        self::assertStringNotContainsString($prompt, $this->lastResponseBody());
        self::assertFileExists($instanceIdFile);
        @unlink($instanceIdFile);
    }

    public function testDisableTelemetry(): void
    {
        $prompt = 'Help us improve <strong>Repman</strong> by enabling sending anonymous usage statistic';
        $instanceIdFile = $this->container()->getParameter('instance_id_file');
        @unlink($instanceIdFile);
        $this->client->request('GET', $this->urlTo('index'));
        self::assertStringContainsString($prompt, $this->lastResponseBody());

        $this->client->request('DELETE', $this->urlTo('admin_config_telemetry_enable'));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->followRedirect();

        self::assertStringNotContainsString($prompt, $this->lastResponseBody());
        self::assertFileExists($instanceIdFile);
        @unlink($instanceIdFile);
    }
}
