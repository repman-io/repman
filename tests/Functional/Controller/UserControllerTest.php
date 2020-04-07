<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class UserControllerTest extends FunctionalTestCase
{
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createAndLoginAdmin();
    }

    public function testRegisterFormRendering(): void
    {
        $this->client->request('GET', $this->urlTo('user_profile'));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Profile', $this->lastResponseBody());
        self::assertStringContainsString('Password', $this->lastResponseBody());
        self::assertStringContainsString('Repeat Password', $this->lastResponseBody());
    }

    public function testChangePassword(): void
    {
        $this->client->request('GET', $this->urlTo('user_profile'));

        $this->client->submitForm('changePassword', [
            'currentPassword' => 'password',
            'plainPassword[first]' => 'secret123',
            'plainPassword[second]' => 'secret123',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));

        $this->client->followRedirect();

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Your password has been changed', $this->lastResponseBody());
    }

    public function testRemoveAccount(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('DELETE', $this->urlTo('user_remove'));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
    }

    public function testResendEmailVerification(): void
    {
        $this->client->request('POST', $this->urlTo('user_resend_verification'));
        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));

        $this->client->followRedirect();

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Email sent successfully', $this->lastResponseBody());
    }
}
