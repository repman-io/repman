<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Ramsey\Uuid\Uuid;

final class UserControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAndLoginAdmin();
    }

    public function testListUsers(): void
    {
        $this->client->request('GET', $this->urlTo('admin_user_list'));

        self::assertEquals(200, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString('test@buddy.works', $this->lastResponseBody());
    }

    public function testDisableUser(): void
    {
        $userId = $this->fixtures->createUser('disabled@buddy.works');

        $this->client->request('POST', $this->urlTo('admin_user_disable', [
            'user' => $userId,
        ]));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_user_list')));
        $this->client->followRedirect();
        self::assertStringContainsString(
            'User disabled@buddy.works has been successfully disabled',
            $this->lastResponseBody()
        );
    }

    public function testDisableUserWhenUserNotFound(): void
    {
        $this->client->request('POST', $this->urlTo('admin_user_disable', [
            'user' => Uuid::uuid4()->toString(), // random
        ]));

        self::assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testEnableUser(): void
    {
        $userId = $this->fixtures->createUser('enabled@buddy.works');

        $this->client->request('POST', $this->urlTo('admin_user_enable', [
            'user' => $userId,
        ]));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_user_list')));
        $this->client->followRedirect();
        self::assertStringContainsString(
            'User enabled@buddy.works has been successfully enabled',
            $this->lastResponseBody()
        );
    }

    public function testEnableUserWhenUserNotFound(): void
    {
        $this->client->request('POST', $this->urlTo('admin_user_enable', [
            'user' => Uuid::uuid4()->toString(), // random
        ]));

        self::assertEquals(404, $this->client->getResponse()->getStatusCode());
    }
}
