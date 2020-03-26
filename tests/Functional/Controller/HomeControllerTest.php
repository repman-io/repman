<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller;

use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class HomeControllerTest extends FunctionalTestCase
{
    public function testHomePage(): void
    {
        $this->client->request('GET', $this->urlTo('index'));

        self::assertStringContainsString('repman-io/composer-plugin', (string) $this->client->getResponse()->getContent());
    }
}
