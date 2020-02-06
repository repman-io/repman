<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class RegistrationControllerTest extends FunctionalTestCase
{
    public function testSuccessfulRegistration(): void
    {
        $this->client->request('GET', $this->urlTo('app_register'));
        $this->client->submitForm('Register', [
            'email' => 'test@buddy.works',
            'plainPassword[first]' => 'secret123',
            'plainPassword[second]' => 'secret123',
            'agreeTerms' => true,
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('organization_create')));

        $this->client->followRedirect();
        self::assertStringContainsString('Your account has been created', $this->lastResponseBody());
    }

    public function testEmailConfirmed(): void
    {
        $this->container()->get(MessageBusInterface::class)->dispatch(new CreateUser(
            '53109f5b-0f4d-47cf-bdba-ffe5d946b1d5',
            'test@buddy.works',
            'secret',
            $confirmToken = 'f731109f-505a-4459-b51d-b142e1046664'
        ));

        $this->client->request('GET', $this->urlTo('app_register_confirm', ['token' => $confirmToken]));
        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));

        $this->client->followRedirect();
        self::assertStringContainsString('E-mail address was confirmed.', $this->lastResponseBody());
    }

    public function testInvalidToken(): void
    {
        $this->container()->get(MessageBusInterface::class)->dispatch(new CreateUser(
            '53109f5b-0f4d-47cf-bdba-ffe5d946b1d5',
            'test@buddy.works',
            'secret',
            $confirmToken = 'f731109f-505a-4459-b51d-b142e1046664'
        ));

        $this->client->request('GET', $this->urlTo('app_register_confirm', ['token' => 'ffffffff-505a-4459-b51d-b142e1046664']));
        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('index')));

        $this->client->followRedirect();
        self::assertStringContainsString('Invalid or expired e-mail confirm token', $this->lastResponseBody());
    }
}
