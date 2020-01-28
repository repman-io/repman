<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class UserControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAndLoginAdmin();
    }

    public function testListUsers(): void
    {
        $this->client->request('GET', '/admin/user');

        self::assertEquals(200, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString('test@buddy.works', (string) $this->client->getResponse()->getContent());
    }
}
