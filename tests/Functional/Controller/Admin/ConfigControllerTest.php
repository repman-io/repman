<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Service\Telemetry\TelemetryEndpoint;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
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
        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_config'));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Repman configuration', $this->lastResponseBody());
        $this->assertStringContainsString('OAuth registration', $this->lastResponseBody());
    }

    public function testToggleAuthenticationOptions(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'local_authentication' => 'login_and_registration',
            'oauth_registration' => 'disabled',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_config')));
        $this->client->followRedirect();
        $this->assertStringContainsString('Configuration has been successfully changed', $this->lastResponseBody());

        $this->client->request(Request::METHOD_GET, $this->urlTo('app_register'));
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_github_start'));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_gitlab_start'));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_bitbucket_start'));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_buddy_start'));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'local_authentication' => 'login_only',
            'oauth_registration' => 'disabled',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_config')));
        $this->client->followRedirect();
        $this->assertStringContainsString('Configuration has been successfully changed', $this->lastResponseBody());

        $this->client->request(Request::METHOD_GET, $this->urlTo('app_register'));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_github_start'));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_gitlab_start'));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_bitbucket_start'));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('register_buddy_start'));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('app_register_confirm', ['token' => '825f33c5-2311-41ec-ba18-e967027b3f6f']));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'local_authentication' => 'login_and_registration',
            'oauth_registration' => 'enabled',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_config')));
        $this->client->followRedirect();
        $this->assertStringContainsString('Configuration has been successfully changed', $this->lastResponseBody());
    }

    public function testEnableTelemetry(): void
    {
        $prompt = 'Help us improve <strong>Repman</strong> by enabling sending anonymous usage statistic';
        $instanceIdFile = $this->instanceIdFile();
        @unlink($instanceIdFile);
        $this->client->request(Request::METHOD_GET, $this->urlTo('index'));
        $this->assertStringContainsString($prompt, $this->lastResponseBody());

        $this->client->request(Request::METHOD_POST, $this->urlTo('admin_config_toggle_telemetry'));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->followRedirect();

        $this->assertStringNotContainsString($prompt, $this->lastResponseBody());
        $this->assertFileExists($instanceIdFile);
        @unlink($instanceIdFile);
    }

    public function testDisableTelemetry(): void
    {
        $prompt = 'Help us improve <strong>Repman</strong> by enabling sending anonymous usage statistic';
        $instanceIdFile = $this->instanceIdFile();
        @unlink($instanceIdFile);
        $this->client->request(Request::METHOD_GET, $this->urlTo('index'));
        $this->assertStringContainsString($prompt, $this->lastResponseBody());

        $this->client->request(Request::METHOD_DELETE, $this->urlTo('admin_config_toggle_telemetry'));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
        $this->client->followRedirect();

        $this->assertStringNotContainsString($prompt, $this->lastResponseBody());
        $this->assertFileExists($instanceIdFile);
        @unlink($instanceIdFile);
    }

    public function testAddTechnicalEmail(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'technical_email' => 'john.doe@example.com',
        ]);

        $instanceId = (string) file_get_contents($this->instanceIdFile());

        $this->assertTrue($this->container()->get(TelemetryEndpoint::class)->wasEmailAdded($instanceId));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_config')));
        $this->client->followRedirect();
        $this->assertStringContainsString('Configuration has been successfully changed', $this->lastResponseBody());
    }

    public function testRemoveTechnicalEmail(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'technical_email' => 'john.doe@example.com',
        ]);

        $instanceId = Uuid::uuid4()->toString();
        file_put_contents($this->instanceIdFile(), $instanceId);

        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'technical_email' => null,
        ]);

        $this->assertTrue($this->container()->get(TelemetryEndpoint::class)->wasEmailRemoved($instanceId));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_config')));
        $this->client->followRedirect();
        $this->assertStringContainsString('Configuration has been successfully changed', $this->lastResponseBody());
    }

    public function testRemoveTechnicalEmailWithoutInstanceId(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'technical_email' => 'john.doe@example.com',
        ]);

        @unlink($this->instanceIdFile());

        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'technical_email' => null,
        ]);

        $this->assertTrue($this->container()->get(TelemetryEndpoint::class)->emailWasNotRemoved());
    }

    private function instanceIdFile(): string
    {
        return (string) $this->container()->getParameter('instance_id_file'); // @phpstan-ignore-line
    }
}
