<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PackageControllerTest extends FunctionalTestCase
{
    public function testWebhook(): void
    {
        $this->fixtures->createPackage('c675c468-6c0f-46bf-a445-65430146c55e');

        $this->client->request(Request::METHOD_POST, '/hook/c675c468-6c0f-46bf-a445-65430146c55e');

        $this->assertSame(Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testWebhookNotFound(): void
    {
        $this->client->request(Request::METHOD_POST, '/hook/c675c468-6c0f-46bf-a445-65430146c55e');

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }
}
