<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class SecurityControllerTest extends FunctionalTestCase
{
    public function testRestrictedPageIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/admin/dist');

        self::assertTrue($this->client->getResponse()->isRedirect('/login'));
    }

    public function testSuccessfulLogin(): void
    {
        $this->createAdmin($email = 'test@buddy.works', $password = 'password');

        $this->client->request('GET', '/login');
        $this->client->submitForm('Sign in', [
            'email' => $email,
            'password' => $password,
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect('/'));

        $this->client->request('GET', '/admin/dist');
        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testSuccessfulLogout(): void
    {
        $this->createAndLoginAdmin();

        $this->client->request('GET', '/admin/dist');
        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->request('GET', '/logout');

        self::assertTrue($this->client->getResponse()->isRedirection());
    }

    public function testPasswordReset(): void
    {
        $this->createAdmin($email = 'test@buddy.works', 'password');

        $this->client->request('GET', '/reset-password');
        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->submitForm('Email me a password reset link', [
            'email' => $email,
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect('/reset-password'));

        $this->client->followRedirect();
        self::assertStringContainsString('email has been sent', (string) $this->client->getResponse()->getContent());

        $token = $this->getUserPasswordResetToken($email);
        $this->client->request('GET', '/reset-password/'.$token);
        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->submitForm('Change password', [
            'password' => 'secret123',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect('/login'));
        $this->client->followRedirect();

        $this->client->submitForm('Sign in', [
            'email' => $email,
            'password' => 'secret123',
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }

    private function getUserPasswordResetToken(string $email): string
    {
        /** @phpstan-var User $user */
        $user = $this->container()->get(UserRepository::class)->findOneBy(['email' => $email]);
        $reflection = new \ReflectionObject($user);
        $property = $reflection->getProperty('resetPasswordToken');
        $property->setAccessible(true);

        return $property->getValue($user);
    }
}
