<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use ReflectionObject;
use Symfony\Component\HttpFoundation\Request;

final class SecurityControllerTest extends FunctionalTestCase
{
    public function testRestrictedPageIsRedirectedToLogin(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_dist_list', ['proxy' => 'packagist.org']));

        $this->assertTrue($this->client->getResponse()->isRedirect('/login'));
    }

    public function testLoginWithInvalidEmail(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('app_login'));
        $this->client->submitForm('Sign in', [
            'email' => 'not@exist.com',
            'password' => 'secret',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
        $this->client->followRedirect();
        $this->assertStringContainsString('Invalid credentials.', $this->lastResponseBody());
    }

    public function testLoginWithInvalidPassword(): void
    {
        $this->fixtures->createAdmin($email = 'test@buddy.works', $password = 'password');
        $this->client->request(Request::METHOD_GET, $this->urlTo('app_login'));
        $this->client->submitForm('Sign in', [
            'email' => $email,
            'password' => 'other',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
        $this->client->followRedirect();
        $this->assertStringContainsString('Invalid credentials.', $this->lastResponseBody());
    }

    public function testLoginCSRFProtection(): void
    {
        $this->fixtures->createAdmin($email = 'test@buddy.works', $password = 'password');

        $this->client->request(Request::METHOD_POST, $this->urlTo('app_login'), [
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));

        $this->client->followRedirect();
        $this->assertStringContainsString('Invalid CSRF token.', $this->lastResponseBody());
    }

    public function testDisabledUserLogin(): void
    {
        $id = $this->fixtures->createAdmin($email = 'test@buddy.works', $password = 'password');
        $this->fixtures->disableUser($id);

        $this->client->request(Request::METHOD_GET, $this->urlTo('app_login'));
        $this->client->submitForm('Sign in', [
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
        $this->client->followRedirect();
        $this->assertStringContainsString('Account is disabled.', $this->lastResponseBody());
    }

    public function testDisabledUserHasNoAccess(): void
    {
        $id = $this->fixtures->createAdmin($email = 'test@buddy.works', $password = 'password');

        $this->client->request(Request::METHOD_GET, $this->urlTo('app_login'));
        $this->client->submitForm('Sign in', [
            'email' => $email,
            'password' => $password,
        ]);

        $this->client->followRedirect();

        $this->fixtures->disableUser($id);
        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_dist_list', ['proxy' => 'packagist.org']));

        // redirected back to login screen
        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
    }

    public function testSuccessfulLogin(): void
    {
        $this->fixtures->createAdmin($email = 'test@buddy.works', $password = 'password');

        $this->client->request(Request::METHOD_GET, '/login');
        $this->client->submitForm('Sign in', [
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));

        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_dist_list', ['proxy' => 'packagist.org']));
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testRedirectToRequestedPathOnSuccessfulLogin(): void
    {
        $this->fixtures->createAdmin($email = 'test@buddy.works', $password = 'password');

        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_dist_list', ['proxy' => 'packagist.org']));
        $this->client->followRedirect();
        $this->client->submitForm('Sign in', [
            'email' => $email,
            'password' => $password,
        ]);

        // authenticator user $targetPath, so in test env localhost will be added to url
        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/admin/dist/packagist.org'));
    }

    public function testSuccessfulLogout(): void
    {
        $this->createAndLoginAdmin();

        $this->client->request(Request::METHOD_GET, $this->urlTo('app_logout'));

        $this->assertTrue($this->client->getResponse()->isRedirection());
    }

    public function testRedirectOnIndexWhenAlreadyLogged(): void
    {
        $this->createAndLoginAdmin();
        $this->client->request(Request::METHOD_GET, $this->urlTo('app_login'));
        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
    }

    public function testPasswordResetForNonExistingUser(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('app_send_reset_password_link'));
        $this->client->submitForm('Email me a password reset link', [
            'email' => 'not@exist.com',
        ]);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_send_reset_password_link')));

        $this->client->followRedirect();
        $this->assertStringContainsString('email has been sent', $this->lastResponseBody());
    }

    public function testPasswordReset(): void
    {
        $this->fixtures->createAdmin($email = 'test@buddy.works', 'password');

        $this->client->request(Request::METHOD_GET, $this->urlTo('app_send_reset_password_link'));
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->client->submitForm('Email me a password reset link', [
            'email' => $email,
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_send_reset_password_link')));

        $this->client->followRedirect();
        $this->assertStringContainsString('email has been sent', (string) $this->client->getResponse()->getContent());

        $token = $this->getUserPasswordResetToken($email);
        $this->client->request(Request::METHOD_GET, $this->urlTo('app_reset_password', ['token' => $token]));
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->client->submitForm('Change password', [
            'password' => 'secret123',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
        $this->client->followRedirect();

        $this->client->submitForm('Sign in', [
            'email' => $email,
            'password' => 'secret123',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));
    }

    public function testPasswordResetWithInvalidToken(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('app_reset_password', ['token' => 'not-exist']));
        $this->client->submitForm('Change password', [
            'password' => 'secret123',
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));

        $this->client->followRedirect();
        $this->assertStringContainsString('Invalid or expired password reset token', (string) $this->client->getResponse()->getContent());
    }

    private function getUserPasswordResetToken(string $email): string
    {
        /** @phpstan-var User $user */
        $user = $this->container()->get(UserRepository::class)->findOneBy(['email' => $email]);
        $reflection = new ReflectionObject($user);
        $property = $reflection->getProperty('resetPasswordToken');
        $property->setAccessible(true);

        return $property->getValue($user);
    }
}
