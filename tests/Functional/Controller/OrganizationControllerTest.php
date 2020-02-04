<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class OrganizationControllerTest extends FunctionalTestCase
{
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createAndLoginAdmin();
    }

    public function testRegisterFormRendering(): void
    {
        $this->client->request('GET', $this->urlTo('organization_create'));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Register organization', $this->lastResponseBody());
        self::assertStringContainsString('Name', $this->lastResponseBody());
    }

    public function testSuccessfulRegistration(): void
    {
        $this->client->request('GET', $this->urlTo('organization_create'));

        $this->client->submitForm('Save', ['name' => 'Acme Inc.']);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create')));

        $this->client->followRedirect();

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Organization &quot;Acme Inc.&quot; has been created', $this->lastResponseBody());
    }

    public function testValidation(): void
    {
        $this->client->request('GET', $this->urlTo('organization_create'));

        $this->client->followRedirects();
        $this->client->submitForm('Save', ['name' => '']);

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('This value should not be blank', $this->lastResponseBody());
    }

    public function testUniqueness(): void
    {
        $this->client->request('GET', $this->urlTo('organization_create'));

        $this->client->followRedirects();
        $this->client->submitForm('Save', ['name' => 'same']);
        $this->client->submitForm('Save', ['name' => 'same']);

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Organization &quot;same&quot; already exists', $this->lastResponseBody());
    }
}
