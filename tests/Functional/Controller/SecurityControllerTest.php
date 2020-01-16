<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

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
}
