<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class IntegrationTestCase extends KernelTestCase
{
    private \Doctrine\Persistence\ObjectManager $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootKernel();
        $this->entityManager = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    protected function container(): ContainerInterface
    {
        return self::$kernel->getContainer()->get('test.service_container');
    }

    protected function entityManager(): \Doctrine\Persistence\ObjectManager
    {
        return $this->entityManager;
    }
}
