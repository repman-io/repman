<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Query\User\OrganizationQuery\DbalOrganizationQuery;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class UserControllerTest extends FunctionalTestCase
{
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createAndLoginAdmin();
    }

    public function testProfileFormRendering(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);
        $this->client->request('GET', $this->urlTo('user_profile'));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Profile', $this->lastResponseBody());
        self::assertStringContainsString('OAuth', $this->lastResponseBody());
        self::assertStringContainsString('Linked to <strong>Github</strong>', $this->lastResponseBody());
        self::assertStringContainsString('Unlink Github', $this->lastResponseBody());
        self::assertStringContainsString('Change password', $this->lastResponseBody());
        self::assertStringContainsString('Current password', $this->lastResponseBody());
        self::assertStringContainsString('New password', $this->lastResponseBody());
        self::assertStringContainsString('Repeat new password', $this->lastResponseBody());
        self::assertStringContainsString('Delete Account', $this->lastResponseBody());
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

        $this->client->setServerParameter('PHP_AUTH_PW', 'secret123');
        $this->client->request('GET', $this->urlTo('user_profile'));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Your password has been changed', $this->lastResponseBody());
    }

    public function testChangeTimezone(): void
    {
        $this->client->request('GET', $this->urlTo('user_profile'));

        self::assertStringContainsString('UTC', $this->lastResponseBody());

        $this->client->submitForm('changeTimezone', [
            'timezone' => 'Europe/Warsaw',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));

        $this->client->followRedirect();

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Your timezone has been changed', $this->lastResponseBody());
        self::assertStringContainsString('Europe/Warsaw', $this->lastResponseBody());
    }

    public function testRemoveAccount(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request('DELETE', $this->urlTo('user_remove'));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));

        self::assertFalse($this->container()->get(DbalOrganizationQuery::class)->getByAlias('buddy')->isEmpty());
    }

    public function testResendEmailVerification(): void
    {
        $this->client->request('POST', $this->urlTo('user_resend_verification'));
        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));

        $this->client->followRedirect();

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Email sent successfully', $this->lastResponseBody());
    }

    public function testRemoveOAuthToken(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);

        $this->client->request('DELETE', $this->urlTo('user_remove_oauth_token', ['type' => 'github']));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));
        $this->client->followRedirect();
        self::assertStringContainsString('Github has been successfully unlinked.', $this->lastResponseBody());
    }

    public function testChangeEmailPreference(): void
    {
        $token = '3c5fb6d5-f8ff-49dd-a420-d8e77d979dc3';
        $this->createAndLoginAdmin('email', 'pass', $token);
        $this->fixtures->confirmUserEmail($token);

        $this->client->request('GET', $this->urlTo('user_profile'));
        $this->client->submitForm('changeEmailPreferences', [
            'emailScanResult' => false,
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));

        $this->client->followRedirect();

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Email preferences have been changed', $this->lastResponseBody());
    }

    public function testApiTokens(): void
    {
        $this->fixtures->createApiToken($this->userId);
        $this->client->request('GET', $this->urlTo('user_api_tokens'));

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Showing 1 to 1 of 1 entries', $this->lastResponseBody());
    }

    public function testGenerateApiTokens(): void
    {
        $this->client->request('GET', $this->urlTo('user_api_token_new'));
        $this->client->submitForm('Generate', [
            'name' => 'to-generate',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_api_tokens')));
        $this->client->followRedirect();
        self::assertStringContainsString('to-generate', $this->lastResponseBody());
    }

    public function testRemoveApiTokens(): void
    {
        $this->fixtures->createApiToken($this->userId, 'to-delete');

        $this->client->request('GET', $this->urlTo('user_api_tokens'));
        self::assertStringContainsString('to-delete', $this->lastResponseBody());

        $this->client->request('DELETE', $this->urlTo('user_api_token_remove', [
            'token' => 'to-delete',
        ]));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_api_tokens')));
        $this->client->followRedirect();
        self::assertStringNotContainsString('to-delete', $this->lastResponseBody());
    }

    public function testRegenerateApiTokens(): void
    {
        $this->fixtures->createApiToken($this->userId, 'to-regenerate');

        $this->client->request('GET', $this->urlTo('user_api_tokens'));
        self::assertStringContainsString('to-regenerate', $this->lastResponseBody());

        $this->client->request('POST', $this->urlTo('user_api_token_regenerate', [
            'token' => 'to-regenerate',
        ]));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_api_tokens')));
        $this->client->followRedirect();
        self::assertStringNotContainsString('to-regenerate', $this->lastResponseBody());
    }
}
