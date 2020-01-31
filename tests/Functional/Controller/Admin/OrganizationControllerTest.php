<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class OrganizationControllerTest extends FunctionalTestCase
{
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = $this->createAndLoginAdmin();
        $this->createOrganization('Acme', $this->userId);
    }

    public function testRegisterFormRendering(): void
    {
        $this->client->request('GET', '/admin/register');

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Register organization', $this->body());
        self::assertStringContainsString('Name', $this->body());
    }

    public function testSuccessfulRegistration(): void
    {
        $this->client->request('GET', '/admin/register');

        $this->client->submitForm('Save', ['register[name]' => 'Acme Inc.']);

        self::assertTrue($this->client->getResponse()->isRedirect('/admin/register'));

        $this->client->followRedirect();

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Organization has been created', $this->body());
    }

    public function testErrors(): void
    {
        $this->client->request('GET', '/admin/register');

        $this->client->followRedirects();
        $this->client->submitForm('Save', ['register[name]' => 'same']);
        $this->client->submitForm('Save', ['register[name]' => 'same']);

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Organization name already exist', $this->body());
    }

    public function testList(): void
    {
        $this->client->request('GET', '/admin/organization');

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Organizations', $this->body());
        self::assertStringContainsString('Acme', $this->body());
    }

    private function body(): string
    {
        return (string) $this->client->getResponse()->getContent();
    }
}
