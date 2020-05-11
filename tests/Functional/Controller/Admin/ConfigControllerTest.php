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
        self::assertStringContainsString('User registration', $this->lastResponseBody());
    }

    public function testToggleRegistration(): void
    {
        $this->client->request('GET', $this->urlTo('admin_config'));
        $this->client->submitForm('save', [
            'user_registration' => 'disabled',
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
            'user_registration' => 'enabled',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_config')));
        $this->client->followRedirect();
        self::assertStringContainsString(
            'Configuration has been successfully changed',
            $this->lastResponseBody()
        );
    }
}
