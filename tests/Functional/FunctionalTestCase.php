<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional;

use Buddy\Repman\Message\CreateUser;
use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class FunctionalTestCase extends WebTestCase
{
    use PHPMatcherAssertions;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function createAdmin(string $email, string $password): void
    {
        $this->container()->get(MessageBusInterface::class)->dispatch(
            new CreateUser(
                Uuid::uuid4()->toString(),
                $email,
                $password,
                ['ROLE_ADMIN']
            )
        );
    }

    protected function createAndLoginAdmin(string $email = 'test@buddy.works', string $password = 'password'): void
    {
        $this->createAdmin($email, $password);
        $this->ensureKernelShutdown(); // TODO im not convinced
        $this->client = static::createClient([], [
            'PHP_AUTH_USER' => $email,
            'PHP_AUTH_PW' => $password,
        ]);
    }

    protected function container(): ContainerInterface
    {
        return self::$kernel->getContainer()->get('test.service_container');
    }
}
