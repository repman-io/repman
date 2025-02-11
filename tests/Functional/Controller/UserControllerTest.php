<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Entity\User\OAuthToken;
use Buddy\Repman\Query\User\OrganizationQuery\DbalOrganizationQuery;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;

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

        $this->client->request(Request::METHOD_GET, $this->urlTo('user_profile'));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Profile', $this->lastResponseBody());
        $this->assertStringContainsString('OAuth', $this->lastResponseBody());
        $this->assertStringContainsString('Linked to <strong>Github</strong>', $this->lastResponseBody());
        $this->assertStringContainsString('Unlink Github', $this->lastResponseBody());
        $this->assertStringContainsString('Change password', $this->lastResponseBody());
        $this->assertStringContainsString('Current password', $this->lastResponseBody());
        $this->assertStringContainsString('New password', $this->lastResponseBody());
        $this->assertStringContainsString('Repeat new password', $this->lastResponseBody());
        $this->assertStringContainsString('Delete Account', $this->lastResponseBody());
    }

    public function testChangePassword(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('user_profile'));

        $this->client->submitForm('changePassword', [
            'currentPassword' => 'password',
            'plainPassword[first]' => 'secret123',
            'plainPassword[second]' => 'secret123',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));

        $this->client->setServerParameter('PHP_AUTH_PW', 'secret123');
        $this->client->request(Request::METHOD_GET, $this->urlTo('user_profile'));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Your password has been changed', $this->lastResponseBody());
    }

    public function testChangeTimezone(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('user_profile'));

        $this->assertStringContainsString('UTC', $this->lastResponseBody());

        $this->client->submitForm('changeTimezone', [
            'timezone' => 'Europe/Warsaw',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));

        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Your timezone has been changed', $this->lastResponseBody());
        $this->assertStringContainsString('Europe/Warsaw', $this->lastResponseBody());
    }

    public function testRemoveAccount(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->client->request(Request::METHOD_DELETE, $this->urlTo('user_remove'));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));

        $this->assertFalse($this->container()->get(DbalOrganizationQuery::class)->getByAlias('buddy')->isEmpty());
    }

    public function testResendEmailVerification(): void
    {
        $this->client->request(Request::METHOD_POST, $this->urlTo('user_resend_verification'));
        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));

        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Email sent successfully', $this->lastResponseBody());
    }

    public function testRemoveOAuthToken(): void
    {
        $this->fixtures->createOrganization('buddy', $this->userId);
        $this->fixtures->createOauthToken($this->userId, OAuthToken::TYPE_GITHUB);

        $this->client->request(Request::METHOD_DELETE, $this->urlTo('user_remove_oauth_token', ['type' => 'github']));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));
        $this->client->followRedirect();
        $this->assertStringContainsString('Github has been successfully unlinked.', $this->lastResponseBody());
    }

    public function testChangeEmailPreference(): void
    {
        $token = '3c5fb6d5-f8ff-49dd-a420-d8e77d979dc3';
        $this->createAndLoginAdmin('email', 'pass', $token);
        $this->fixtures->confirmUserEmail($token);

        $this->client->request(Request::METHOD_GET, $this->urlTo('user_profile'));
        $this->client->submitForm('changeEmailPreferences', [
            'emailScanResult' => false,
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_profile')));

        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Email preferences have been changed', $this->lastResponseBody());
    }

    public function testApiTokens(): void
    {
        $this->fixtures->createApiToken($this->userId);
        $this->client->request(Request::METHOD_GET, $this->urlTo('user_api_tokens'));

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString('Showing 1 to 1 of 1 entries', $this->lastResponseBody());
    }

    public function testGenerateApiTokens(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('user_api_token_new'));
        $this->client->submitForm('Generate', [
            'name' => 'to-generate',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_api_tokens')));
        $this->client->followRedirect();
        $this->assertStringContainsString('to-generate', $this->lastResponseBody());
    }

    public function testRemoveApiTokens(): void
    {
        $this->fixtures->createApiToken($this->userId, 'to-delete');

        $this->client->request(Request::METHOD_GET, $this->urlTo('user_api_tokens'));
        $this->assertStringContainsString('to-delete', $this->lastResponseBody());

        $this->client->request(Request::METHOD_DELETE, $this->urlTo('user_api_token_remove', [
            'token' => 'to-delete',
        ]));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_api_tokens')));
        $this->client->followRedirect();
        $this->assertStringNotContainsString('to-delete', $this->lastResponseBody());
    }

    public function testRegenerateApiTokens(): void
    {
        $this->fixtures->createApiToken($this->userId, 'to-regenerate');

        $this->client->request(Request::METHOD_GET, $this->urlTo('user_api_tokens'));
        $this->assertStringContainsString('to-regenerate', $this->lastResponseBody());

        $this->client->request(Request::METHOD_POST, $this->urlTo('user_api_token_regenerate', [
            'token' => 'to-regenerate',
        ]));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('user_api_tokens')));
        $this->client->followRedirect();
        $this->assertStringNotContainsString('to-regenerate', $this->lastResponseBody());
    }
}
