<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use function Ramsey\Uuid\v4;

final class IndexControllerTest extends FunctionalTestCase
{
    public function testHomePage(): void
    {
        $this->client->request('GET', $this->urlTo('index'));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
    }

    public function testHomePageWhenLogged(): void
    {
        $this->createAndLoginAdmin();
        $this->client->request('GET', $this->urlTo('index'));

        self::assertStringContainsString('repman-io/composer-plugin', (string) $this->client->getResponse()->getContent());
    }

    public function testRedirectToInvitationWhenTokenExist(): void
    {
        $token = v4();
        $this->createAndLoginAdmin();

        $this->client->disableReboot();
        // start new session
        $this->client->request('GET', $this->urlTo('index'));
        $session = $this->client->getRequest()->getSession();
        $session->set('organization-token', $token);
        // since Symfony 6 the session is written at the end of a request, not by a shared service
        $session->save();
        $this->client->request('GET', $this->urlTo('index'));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_accept_invitation', [
            'token' => $token,
        ])));
    }
}
