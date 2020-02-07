<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Service\Organization\TokenGenerator;
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

    public function testNameCantBeEmpty(): void
    {
        $this->client->request('GET', $this->urlTo('organization_create'));

        $this->client->followRedirects();
        $this->client->submitForm('Save', ['name' => '']);

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('This value should not be blank', $this->lastResponseBody());
    }

    public function testInvalidName(): void
    {
        $this->client->request('GET', $this->urlTo('organization_create'));

        $this->client->followRedirects();
        $this->client->submitForm('Save', ['name' => '!@#']); // only special chars

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Name cannot consist of special characters only.', $this->lastResponseBody());
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

    public function testOverview(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_overview', ['organization' => 'buddy']));

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testOverviewNotAllowedForNotOwnedOrganization(): void
    {
        $otherId = $this->fixtures->createAdmin('cto@buddy.works', 'strong');
        $this->fixtures->createOrganization('buddy', $otherId);
        $this->client->request('GET', $this->urlTo('organization_overview', ['organization' => 'buddy']));

        self::assertTrue($this->client->getResponse()->isForbidden());
    }

    public function testPackages(): void
    {
        $anotherUserID = $this->fixtures->createUser('another@user.com', 'secret');

        $buddyId = $this->fixtures->createOrganization('buddy', $this->userId);
        $anotherOrgId = $this->fixtures->createOrganization('google', $anotherUserID);

        $this->fixtures->addPackage($buddyId, 'https://buddy.com');
        $this->fixtures->addPackage($anotherOrgId, 'https://google.com');

        $this->client->request('GET', $this->urlTo('organization_packages', ['organization' => 'buddy']));

        self::assertTrue($this->client->getResponse()->isOk());

        self::assertStringContainsString(
            'Showing 1 to 1 of 1 entries',
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testAddPackage(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_package_new', ['organization' => 'buddy']));

        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->submitForm('Add', [
            'url' => 'http://guthib.com',
        ]);

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_packages', ['organization' => 'buddy']))
        );

        $this->client->followRedirect();
        self::assertStringContainsString('Package has been added', (string) $this->client->getResponse()->getContent());

        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testGenerateNewToken(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('GET', $this->urlTo('organization_token_new', ['organization' => 'buddy']));
        $this->client->submitForm('Generate', [
            'name' => 'Production Token',
        ]);

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_tokens', ['organization' => 'buddy']))
        );

        $this->client->followRedirect();
        self::assertStringContainsString('Production Token', $this->lastResponseBody());
    }

    public function testRegenerateToken(): void
    {
        $this->fixtures->createToken(
            $this->fixtures->createOrganization('buddy', $this->userId),
            'secret-token'
        );
        $this->container()->get(TokenGenerator::class)->setNextToken('regenerated-token');
        $this->client->request('POST', $this->urlTo('organization_token_regenerate', [
            'organization' => 'buddy',
            'token' => 'secret-token',
        ]));

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_tokens', ['organization' => 'buddy']))
        );
        $this->client->followRedirect();
        self::assertStringContainsString('regenerated-token', $this->lastResponseBody());
    }

    public function testRemoveToken(): void
    {
        $this->fixtures->createToken(
            $this->fixtures->createOrganization('buddy', $this->userId),
            'secret-token'
        );
        $this->client->request('DELETE', $this->urlTo('organization_token_remove', [
            'organization' => 'buddy',
            'token' => 'secret-token',
        ]));

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_tokens', ['organization' => 'buddy']))
        );
        $this->client->followRedirect();
        self::assertStringNotContainsString('secret-token', $this->lastResponseBody());
    }
}
