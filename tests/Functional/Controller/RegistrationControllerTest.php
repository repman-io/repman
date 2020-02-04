<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class RegistrationControllerTest extends FunctionalTestCase
{
    public function testSuccessfulRegistration(): void
    {
        $this->client->request('GET', '/register');
        $this->client->submitForm('Register', [
            'email' => 'test@buddy.works',
            'plainPassword[first]' => 'secret123',
            'plainPassword[second]' => 'secret123',
            'agreeTerms' => true,
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect('/'));

        $this->client->followRedirect();
        self::assertStringContainsString('Your account has been created', (string) $this->client->getResponse()->getContent());
    }
}
