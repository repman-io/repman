<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Admin;

use Buddy\Repman\Query\Admin\UserQuery\DbalUserQuery;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UserControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAndLoginAdmin();
    }

    public function testListUsers(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_user_list'));

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertStringContainsString('test@buddy.works', $this->lastResponseBody());
    }

    public function testDisableUser(): void
    {
        $userId = $this->fixtures->createUser('disabled@buddy.works');

        $this->client->request(Request::METHOD_POST, $this->urlTo('admin_user_disable', [
            'user' => $userId,
        ]));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_user_list')));
        $this->client->followRedirect();
        $this->assertStringContainsString('User disabled@buddy.works has been successfully disabled', $this->lastResponseBody());
    }

    public function testDisableUserWhenUserNotFound(): void
    {
        $this->client->request(Request::METHOD_POST, $this->urlTo('admin_user_disable', [
            'user' => Uuid::uuid4()->toString(), // random
        ]));

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testEnableUser(): void
    {
        $userId = $this->fixtures->createUser('enabled@buddy.works');

        $this->client->request(Request::METHOD_POST, $this->urlTo('admin_user_enable', [
            'user' => $userId,
        ]));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->urlTo('admin_user_list')));
        $this->client->followRedirect();
        $this->assertStringContainsString('User enabled@buddy.works has been successfully enabled', $this->lastResponseBody());
    }

    public function testEnableUserWhenUserNotFound(): void
    {
        $this->client->request(Request::METHOD_POST, $this->urlTo('admin_user_enable', [
            'user' => Uuid::uuid4()->toString(), // random
        ]));

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testChangeRoles(): void
    {
        $userId = $this->fixtures->createUser('typical@buddy.works');
        $this->client->followRedirects();
        $this->client->request(Request::METHOD_GET, $this->urlTo('admin_user_roles', [
            'user' => $userId,
        ]));
        $this->client->submitForm('Change roles', ['admin' => true]);

        $this->assertStringContainsString('User typical@buddy.works roles has been successfully changed', $this->lastResponseBody());
        $this->assertContains('ROLE_ADMIN', $this->container()->get(DbalUserQuery::class)->getById($userId)->get()->roles());
    }
}
