<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration\MessageHandler\Proxy;

use Buddy\Repman\Message\Proxy\RemoveDist;
use Buddy\Repman\Service\Proxy\PackageManager;
use Buddy\Repman\Tests\Integration\IntegrationTestCase;
use Munus\Collection\GenericList;
use Symfony\Component\Messenger\MessageBusInterface;

final class RemoveDistHandlerTest extends IntegrationTestCase
{
    public function testRemoveDistByPackageName(): void
    {
        $this->container()->get(MessageBusInterface::class)->dispatch(
            new RemoveDist('packagist.org', 'some-vendor/some-name')
        );

        self::assertTrue(GenericList::ofAll(['buddy-works/repman'])->equals(
            $this->container()->get(PackageManager::class)->packages('packagist.org')
        ));
    }
}
