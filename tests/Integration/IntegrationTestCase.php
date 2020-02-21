<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class IntegrationTestCase extends KernelTestCase
{
    protected FixturesManager $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootKernel();
        $this->fixtures = new FixturesManager(self::$kernel->getContainer()->get('test.service_container'));
    }

    protected function container(): TestContainer
    {
        return self::$kernel->getContainer()->get('test.service_container');
    }

    protected function dispatchMessage(object $message): void
    {
        $this->container()->get(MessageBusInterface::class)->dispatch($message);
    }
}
